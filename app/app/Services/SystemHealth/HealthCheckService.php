<?php

namespace App\Services\SystemHealth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckService
{
    public function runEnvironmentChecks(): array
    {
        return [
            $this->runCheck('PHP Version', fn () => [
                PHP_VERSION_ID >= 80200,
                'Running PHP ' . PHP_VERSION . ' (requires 8.2+)',
            ]),
            $this->runCheck('Laravel Version', fn () => [
                true,
                'Laravel ' . app()->version(),
            ]),
            $this->runCheck('Environment', function () {
                $env = app()->environment();
                $debug = config('app.debug');
                $ok = $env !== 'production' || ! $debug;
                $msg = "APP_ENV={$env}, APP_DEBUG=" . ($debug ? 'true' : 'false');
                return [$ok, $msg];
            }),
            $this->runCheck('App Key', fn () => [
                ! empty(config('app.key')),
                empty(config('app.key')) ? 'APP_KEY is not set' : 'APP_KEY is set',
            ]),
            $this->runCheck('Database Connection', function () {
                DB::connection()->getPdo();
                $driver = DB::connection()->getDriverName();
                $name = DB::connection()->getDatabaseName();
                return [true, "Connected to {$driver} ({$name})"];
            }),
            $this->runCheck('Cache Driver', function () {
                $key = 'system_health_cache_probe_' . uniqid();
                Cache::put($key, 'ok', 5);
                $read = Cache::get($key);
                Cache::forget($key);
                return [$read === 'ok', 'Driver: ' . config('cache.default')];
            }),
            $this->runCheck('Storage Writable', function () {
                $disk = Storage::disk(config('filesystems.default'));
                $file = 'system_health_probe_' . uniqid() . '.txt';
                $disk->put($file, 'ok');
                $ok = $disk->exists($file);
                $disk->delete($file);
                return [$ok, 'Disk: ' . config('filesystems.default')];
            }),
            $this->runCheck('Queue Driver', fn () => [
                true,
                'Driver: ' . config('queue.default'),
            ]),
            $this->runCheck('Session Driver', fn () => [
                true,
                'Driver: ' . config('session.driver'),
            ]),
            $this->runCheck('Mail Driver', fn () => [
                true,
                'Driver: ' . config('mail.default'),
            ]),
        ];
    }

    public function runSchemaChecks(): array
    {
        $expected = [
            'users', 'customers', 'properties', 'estimates', 'estimate_line_items',
            'invoices', 'invoice_credits', 'jobs', 'vendors', 'services', 'packages',
            'crews', 'crew_members', 'employees', 'time_logs', 'chemical_logs',
            'messages', 'notifications', 'settings', 'roles', 'role_permissions',
            'property_media', 'marketing_campaigns',
        ];

        $checks = [];
        foreach ($expected as $table) {
            $checks[] = $this->runCheck("Table: {$table}", function () use ($table) {
                $exists = Schema::hasTable($table);
                $count = $exists ? DB::table($table)->count() : 0;
                return [$exists, $exists ? "exists, {$count} rows" : 'MISSING'];
            });
        }

        return $checks;
    }

    public function runRouteChecks(): array
    {
        $resources = [
            'Customers' => '/customers',
            'Properties' => '/properties',
            'Estimates' => '/estimates',
            'Invoices' => '/invoices',
            'Jobs' => '/jobs',
            'Vendors' => '/vendors',
            'Services' => '/services',
            'Packages' => '/packages',
            'Employees' => '/employees',
            'Crews' => '/crews',
            'Time Logs' => '/time-logs',
            'Chemical Logs' => '/chemical-logs',
            'Messages' => '/messages',
            'Notifications' => '/notifications',
            'Settings' => '/settings',
        ];

        $checks = [];
        foreach ($resources as $label => $path) {
            $checks[] = $this->runCheck("Route: {$label}", function () use ($path) {
                $routes = app('router')->getRoutes();
                foreach ($routes as $route) {
                    if ('/' . ltrim($route->uri(), '/') === $path) {
                        return [true, "{$path} registered"];
                    }
                }
                return [false, "{$path} not found in route table"];
            });
        }

        return $checks;
    }

    protected function runCheck(string $name, callable $fn): array
    {
        $start = microtime(true);
        try {
            [$passed, $message] = $fn();
        } catch (Throwable $e) {
            return [
                'name' => $name,
                'passed' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        }

        return [
            'name' => $name,
            'passed' => (bool) $passed,
            'message' => (string) $message,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
        ];
    }
}
