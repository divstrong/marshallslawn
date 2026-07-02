<?php

namespace Database\Seeders;

use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Estimate;
use App\Models\Job;
use App\Models\Property;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\TimeLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Predictable demo data for the native app: one Foreman / Field / Estimator
 * test account plus a crew, today's route, jobs, and quotes to populate
 * each role's experience. Safe to run repeatedly.
 */
class MobileDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Optionally make EVERY employee sign in with "password" for a full
        // dev/staging reset. This is a blanket password reset, so it is opt-in
        // and never runs against real accounts on production unless explicitly
        // enabled (DEMO_SEED_RESET_PASSWORDS=true). The three demo accounts
        // below always get "password" regardless, so demo logins keep working.
        if ($this->shouldResetAllPasswords()) {
            Employee::query()->update(['password' => bcrypt('password')]);
            $this->command?->warn('Reset ALL employee passwords to "password".');
        }

        $foreman = $this->employee('foreman@marshallslawn.test', 'Frank', 'Foreman', 'foreman');
        $field = $this->employee('field@marshallslawn.test', 'Felix', 'Field', 'field');
        $estimator = $this->employee('estimator@marshallslawn.test', 'Erin', 'Estimator', 'estimator');

        // Demo crew: Frank is foreman, Felix is a member.
        $crew = Crew::updateOrCreate(
            ['name' => 'Demo Crew'],
            ['foreman_id' => $foreman->id, 'status' => 'active', 'division' => 'Lawn']
        );
        CrewMember::updateOrCreate(['crew_id' => $crew->id, 'employee_id' => $field->id]);

        // Wipe any previous demo output so re-running stays clean.
        $demoCustomerIds = Customer::where('source', 'mobile-demo')->pluck('id');
        Job::whereIn('customer_id', $demoCustomerIds)->orWhere('crew_id', $crew->id)->delete();
        RouteStop::whereIn('route_id', Route::where('crew_id', $crew->id)->pluck('id'))->delete();
        Route::where('crew_id', $crew->id)->delete();
        Estimate::where('created_by', $estimator->id)->delete();

        $customers = $this->customers();
        $jobs = $this->jobs($crew->id, $customers);
        $this->todaysRoute($crew->id, $jobs);
        $this->quotes($estimator->id, $customers);
        $this->shiftHistory([$foreman->id, $field->id]);

        $this->command?->info('Demo accounts ready — password "password":');
        $this->command?->line('  foreman@marshallslawn.test   (Foreman)');
        $this->command?->line('  field@marshallslawn.test     (Field)');
        $this->command?->line('  estimator@marshallslawn.test (Estimator)');
    }

    /**
     * Whether to reset every employee's password to the shared test password.
     * Safe by default: only on local/testing, or when explicitly opted in via
     * DEMO_SEED_RESET_PASSWORDS=true — so it won't clobber real accounts on live.
     */
    private function shouldResetAllPasswords(): bool
    {
        return app()->environment('local', 'testing')
            || filter_var(env('DEMO_SEED_RESET_PASSWORDS', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function employee(string $email, string $first, string $last, string $role): Employee
    {
        return Employee::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $first,
                'last_name' => $last,
                'name' => "{$first} {$last}",
                'role' => $role,
                'status' => 'active',
                'password' => 'password',
                'phone' => '555-0100',
                'mobile_phone' => '555-0100',
                'city' => 'Springfield',
                'state' => 'IL',
                'division' => 'Lawn',
            ]
        );
    }

    /**
     * @return array<int, array{customer: Customer, property: Property}>
     */
    private function customers(): array
    {
        $seed = [
            ['Maple Grove HOA', 'Dana', 'Powell', '120 Maple Ave', 'Springfield', 'IL', '62701'],
            ['Riverside Offices', 'Marcus', 'Reed', '88 River Rd', 'Springfield', 'IL', '62702'],
            ['Oakwood Residence', 'Helen', 'Carter', '14 Oak Street', 'Chatham', 'IL', '62629'],
            ['Cedar Point Plaza', 'Tony', 'Bishop', '500 Cedar Blvd', 'Springfield', 'IL', '62703'],
            ['Willowbrook Estate', 'Grace', 'Nguyen', '7 Willow Lane', 'Rochester', 'IL', '62563'],
            ['Pine Hill Apartments', 'Sam', 'Doyle', '230 Pine Hill Dr', 'Springfield', 'IL', '62704'],
        ];

        $records = [];

        foreach ($seed as $index => [$name, $firstName, $lastName, $address, $city, $state, $zip]) {
            $customer = Customer::updateOrCreate(
                ['company_name' => $name, 'source' => 'mobile-demo'],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => 'demo' . ($index + 1) . '@marshallslawn.test',
                    'phone' => '555-02' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'status' => 'active',
                ]
            );

            $property = Property::updateOrCreate(
                ['customer_id' => $customer->id, 'address' => $address],
                [
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'square_footage' => 4000 + ($index * 1500),
                    'is_primary' => true,
                ]
            );

            $records[] = ['customer' => $customer, 'property' => $property];
        }

        return $records;
    }

    /**
     * Five jobs today (route order) plus a couple either side.
     *
     * @param  array<int, array{customer: Customer, property: Property}>  $customers
     * @return array<int, Job>
     */
    private function jobs(int $crewId, array $customers): array
    {
        $today = Carbon::today();

        $plan = [
            ['Weekly Mowing', 'in_progress', 'normal', $today, true, false],
            ['Hedge Trimming', 'scheduled', 'high', $today, false, false],
            ['Fertilizer Application', 'scheduled', 'normal', $today, false, false],
            ['Spring Cleanup', 'scheduled', 'normal', $today, false, false],
            ['Mulch Installation', 'scheduled', 'low', $today, false, false],
            ['Leaf Removal', 'completed', 'normal', $today->copy()->subDay(), true, true],
            ['Irrigation Check', 'scheduled', 'normal', $today->copy()->addDay(), false, false],
        ];

        $jobs = [];

        foreach ($plan as $index => [$title, $status, $priority, $date, $started, $finished]) {
            $entry = $customers[$index % count($customers)];

            $jobs[] = Job::create([
                'customer_id' => $entry['customer']->id,
                'property_id' => $entry['property']->id,
                'crew_id' => $crewId,
                'title' => $title,
                'description' => "{$title} service for {$entry['customer']->company_name}.",
                'status' => $status,
                'priority' => $priority,
                'scheduled_date' => $date,
                'completed_date' => $finished ? $date : null,
                'started_at' => $started ? $date->copy()->setTime(8, 30) : null,
                'finished_at' => $finished ? $date->copy()->setTime(10, 15) : null,
            ]);
        }

        return $jobs;
    }

    /**
     * Today's route, stops ordered to match the first five jobs.
     *
     * @param  array<int, Job>  $jobs
     */
    private function todaysRoute(int $crewId, array $jobs): void
    {
        $route = Route::create([
            'name' => 'Monday Lawn Route',
            'route_date' => Carbon::today(),
            'crew_id' => $crewId,
            'status' => 'active',
            'notes' => 'Start at the north end and work south.',
        ]);

        foreach (array_slice($jobs, 0, 5) as $index => $job) {
            RouteStop::create([
                'route_id' => $route->id,
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'property_id' => $job->property_id,
                'sort_order' => $index + 1,
                'status' => $job->status === 'in_progress' ? 'in_progress' : 'pending',
            ]);
        }
    }

    /**
     * @param  array<int, array{customer: Customer, property: Property}>  $customers
     */
    private function quotes(int $estimatorId, array $customers): void
    {
        $plan = [
            ['draft', [['Lawn mowing — seasonal contract', 28, 45], ['Edging & trimming', 28, 15]]],
            ['sent', [['Landscape design consultation', 1, 350], ['Mulch & bed prep', 6, 85]]],
            ['accepted', [['Tree & shrub pruning', 1, 480]]],
            ['draft', [['Irrigation system install', 1, 2200], ['Spring activation', 1, 120]]],
        ];

        foreach ($plan as $index => [$status, $lines]) {
            $entry = $customers[$index % count($customers)];

            $estimate = Estimate::create([
                'customer_id' => $entry['customer']->id,
                'property_id' => $entry['property']->id,
                'created_by' => $estimatorId,
                'status' => $status,
                'square_footage' => $entry['property']->square_footage,
                'valid_until' => Carbon::today()->addDays(30),
                'notes' => 'Prepared from the mobile app.',
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'sent_at' => $status === 'sent' || $status === 'accepted' ? now() : null,
                'accepted_at' => $status === 'accepted' ? now() : null,
            ]);

            $subtotal = 0.0;
            foreach ($lines as $order => [$description, $qty, $price]) {
                $lineTotal = round($qty * $price, 2);
                $subtotal += $lineTotal;
                $estimate->lineItems()->create([
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total' => $lineTotal,
                    'sort_order' => $order + 1,
                ]);
            }

            $estimate->update(['subtotal' => $subtotal, 'tax' => 0, 'total' => $subtotal]);
        }
    }

    /**
     * A couple of closed shifts so the Time screen history isn't empty.
     *
     * @param  array<int, int>  $employeeIds
     */
    private function shiftHistory(array $employeeIds): void
    {
        foreach ($employeeIds as $employeeId) {
            TimeLog::where('employee_id', $employeeId)->delete();

            foreach ([1, 2] as $daysAgo) {
                $clockIn = Carbon::today()->subDays($daysAgo)->setTime(7, 30);
                TimeLog::create([
                    'employee_id' => $employeeId,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockIn->copy()->setTime(16, 0),
                    'break_minutes' => 30,
                    'status' => 'completed',
                ]);
            }
        }
    }
}
