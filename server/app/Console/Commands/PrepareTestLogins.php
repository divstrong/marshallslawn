<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Make every employee able to sign in to the native app for testing: sets a
 * shared password and backfills a unique email for anyone missing one. Roles
 * are left untouched, so each user gets their own role's experience.
 *
 * Re-runnable: existing emails are preserved; only the password is reset.
 */
class PrepareTestLogins extends Command
{
    protected $signature = 'users:prepare-test-logins
                            {--password=password : Password to set for every account}
                            {--domain=test.marshallslawn.app : Domain for generated emails}
                            {--include-inactive : Also include non-active employees}';

    protected $description = 'Set a test password for all employees and backfill missing emails so they can log into the app';

    public function handle(): int
    {
        $password = (string) $this->option('password');
        $domain = (string) $this->option('domain');

        $query = Employee::query();
        if (! $this->option('include-inactive')) {
            $query->where('status', 'active');
        }

        $employees = $query->orderBy('id')->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees matched.');

            return self::SUCCESS;
        }

        // Existing emails (lowercased) we must not collide with when generating.
        $taken = Employee::query()
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($e) => strtolower($e))
            ->flip();

        $backfilled = 0;
        $passwordsSet = 0;

        foreach ($employees as $employee) {
            if (empty($employee->email)) {
                $employee->email = $this->uniqueEmail($employee, $domain, $taken);
                $taken->put(strtolower($employee->email), true);
                $backfilled++;
            }

            $employee->password = $password; // hashed by the model's cast on save
            $employee->save();
            $passwordsSet++;
        }

        $this->info("✓ Passwords set for {$passwordsSet} employee(s) (password: \"{$password}\").");
        $this->info("✓ Backfilled {$backfilled} missing email(s) @ {$domain}.");

        $this->newLine();
        $this->line('Sample logins by role:');
        $this->table(
            ['Role', 'Name', 'Email'],
            $employees
                ->groupBy(fn ($e) => $e->role ?: 'field')
                ->flatMap(fn ($group) => $group->take(2))
                ->map(fn ($e) => [
                    $e->role ?: 'field',
                    trim("{$e->first_name} {$e->last_name}") ?: $e->name,
                    $e->email,
                ])
                ->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $taken
     */
    private function uniqueEmail(Employee $employee, string $domain, $taken): string
    {
        $base = Str::slug(trim("{$employee->first_name} {$employee->last_name}"), '.');
        if ($base === '') {
            $base = Str::slug($employee->name ?: 'user', '.') ?: 'user';
        }

        // The id keeps it unique within the generated set; loop guards the rare
        // case it still collides with a pre-existing address.
        $email = "{$base}.{$employee->id}@{$domain}";
        while ($taken->has(strtolower($email))) {
            $email = "{$base}.{$employee->id}." . Str::lower(Str::random(4)) . "@{$domain}";
        }

        return $email;
    }
}
