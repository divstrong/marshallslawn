<?php

namespace App\Livewire\SystemHealth;

use App\Services\SystemHealth\HealthCheckService;
use App\Services\SystemHealth\SmokeTestService;
use Livewire\Component;

class Overview extends Component
{
    public array $results = [];
    public ?string $lastRunAt = null;
    public bool $running = false;

    public function runAll(): void
    {
        $this->running = true;

        $env = app(HealthCheckService::class)->runEnvironmentChecks();
        $schema = app(HealthCheckService::class)->runSchemaChecks();
        $routes = app(HealthCheckService::class)->runRouteChecks();
        $smoke = app(SmokeTestService::class)->runAll();

        $this->results = [
            'Environment' => $env,
            'Schema' => $schema,
            'Routes' => $routes,
            'Smoke Tests' => $smoke,
        ];

        $this->lastRunAt = now()->format('M j, Y g:i:s A');
        $this->running = false;
    }

    public function render()
    {
        return view('livewire.system-health.overview');
    }
}
