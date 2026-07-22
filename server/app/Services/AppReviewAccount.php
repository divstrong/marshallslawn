<?php

namespace App\Services;

use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Employee;
use App\Models\Job;
use Illuminate\Support\Facades\Log;

/**
 * The App Store review demo account.
 *
 * Sign-in is passwordless, so a reviewer cannot receive a one-time code. When
 * `config/app_review.php` is populated, this service recognises the configured
 * address, accepts a fixed code in place of an emailed one, and provisions the
 * employee record on demand — so a submission needs no manual database work.
 *
 * Everything here is inert unless both the email and the code are configured.
 */
class AppReviewAccount
{
    /** Whether the bypass is switched on at all. */
    public function enabled(): bool
    {
        return $this->email() !== '' && $this->code() !== '';
    }

    /** Whether this address is the configured review account. */
    public function matchesEmail(string $email): bool
    {
        return $this->enabled() && $this->email() === strtolower(trim($email));
    }

    /** Whether this is the configured fixed code. */
    public function matchesCode(string $code): bool
    {
        // The code is public by design, so this is not guarding a secret — it
        // is compared in constant time purely to keep the habit consistent.
        return $this->enabled() && hash_equals($this->code(), trim($code));
    }

    /**
     * Find or create the demo employee, and make sure it can see a crew's work
     * so the reviewer does not open an app with empty Jobs and Schedule tabs.
     */
    public function provision(): Employee
    {
        $email = $this->email();

        $employee = Employee::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $employee) {
            $employee = Employee::create([
                'name' => 'App Review (Demo)',
                'first_name' => 'App',
                'last_name' => 'Review',
                'email' => $email,
                'status' => 'active',
                'role' => (string) config('app_review.role', 'foreman'),
            ]);

            Log::info('app_review.account_provisioned', ['employee_id' => $employee->id]);
        }

        // A record that was deactivated (or had its role changed) between
        // submissions would silently break the next review.
        if ($employee->status !== 'active' || $employee->role !== config('app_review.role')) {
            $employee->update([
                'status' => 'active',
                'role' => (string) config('app_review.role', 'foreman'),
            ]);
        }

        $this->attachToCrew($employee);

        return $employee;
    }

    /**
     * Jobs and Schedule are scoped by crew membership. Join the configured
     * crew, or the one with the most upcoming work, so the demo account has
     * something real to show.
     *
     * The account joins as a *member*, never as the foreman, so it can never
     * displace a real foreman's pin on the dispatch map.
     */
    private function attachToCrew(Employee $employee): void
    {
        if (CrewMember::query()->where('employee_id', $employee->id)->exists()) {
            return;
        }

        $crewId = config('app_review.crew_id');

        if ($crewId === null) {
            $crewId = Job::query()
                ->whereNotNull('crew_id')
                ->whereDate('scheduled_date', '>=', now()->toDateString())
                ->selectRaw('crew_id, COUNT(*) as job_count')
                ->groupBy('crew_id')
                ->orderByDesc('job_count')
                ->value('crew_id');
        }

        if ($crewId === null || ! Crew::query()->whereKey($crewId)->exists()) {
            Log::warning('app_review.no_crew_available', ['employee_id' => $employee->id]);

            return;
        }

        CrewMember::create([
            'crew_id' => $crewId,
            'employee_id' => $employee->id,
        ]);

        Log::info('app_review.account_joined_crew', [
            'employee_id' => $employee->id,
            'crew_id' => $crewId,
        ]);
    }

    private function email(): string
    {
        return strtolower(trim((string) config('app_review.email', '')));
    }

    private function code(): string
    {
        return trim((string) config('app_review.code', ''));
    }
}
