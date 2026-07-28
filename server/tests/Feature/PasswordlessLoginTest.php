<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeLoginCode;
use App\Models\Job;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordlessLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // The per-email throttle is shared state; clear it so tests that request
        // several codes for the same address don't trip each other.
        RateLimiter::clear('login-code:crew@marshallslawn.com');
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'name' => 'Crew Member',
            'first_name' => 'Crew',
            'last_name' => 'Member',
            'email' => 'crew@marshallslawn.com',
            'status' => 'active',
            'role' => 'foreman',
        ], $overrides));
    }

    /** Pull the plain code out of the mailable the controller queued. */
    private function sentCode(): string
    {
        $code = null;

        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }

    public function test_active_employee_can_sign_in_with_an_emailed_code(): void
    {
        $employee = $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])
            ->assertOk()
            ->assertJsonPath('expires_in_minutes', 10);

        $response = $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $this->sentCode(),
        ]);

        $response->assertOk()
            ->assertJsonPath('employee.id', $employee->id)
            ->assertJsonStructure(['token', 'employee']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame(1, $employee->tokens()->count());
    }

    public function test_the_stored_code_is_hashed_not_plain_text(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();

        $row = EmployeeLoginCode::firstOrFail();
        $code = $this->sentCode();

        $this->assertNotSame($code, $row->code_hash);
        $this->assertTrue(Hash::check($code, $row->code_hash));
    }

    public function test_a_code_cannot_be_redeemed_twice(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $code = $this->sentCode();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $code,
        ])->assertOk();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $code,
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_requesting_a_new_code_invalidates_the_previous_one(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $firstCode = $this->sentCode();

        Mail::fake();
        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $secondCode = $this->sentCode();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $firstCode,
        ])->assertStatus(422);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $secondCode,
        ])->assertOk();
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $code = $this->sentCode();

        $this->travel(11)->minutes();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $code,
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_a_code_is_burned_after_five_wrong_guesses(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $code = $this->sentCode();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/verify-code', [
                'email' => 'crew@marshallslawn.com',
                'code' => '000000',
            ])->assertStatus(422);
        }

        // Even the correct code is dead once the attempt budget is spent.
        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $code,
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_inactive_employees_cannot_request_a_code(): void
    {
        $this->employee(['status' => 'terminated']);

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_an_employee_deactivated_after_requesting_a_code_cannot_verify_it(): void
    {
        $employee = $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        $code = $this->sentCode();

        $employee->update(['status' => 'terminated']);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $code,
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_unknown_emails_are_rejected_without_sending_mail(): void
    {
        $this->postJson('/api/auth/request-code', ['email' => 'nobody@marshallslawn.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_email_matching_is_case_insensitive(): void
    {
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'CREW@Marshallslawn.com'])->assertOk();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'Crew@MARSHALLSLAWN.com',
            'code' => $this->sentCode(),
        ])->assertOk();
    }

    public function test_code_requests_are_throttled_per_email(): void
    {
        $this->employee();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        }

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_the_password_login_route_is_gone(): void
    {
        $this->postJson('/api/login', [
            'email' => 'crew@marshallslawn.com',
            'password' => 'whatever',
        ])->assertNotFound();
    }

    /** A scheduled job for a crew, so "busiest crew" has something to count. */
    private function jobFor(Crew $crew, string $title): Job
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '1 Elm St',
            'city' => 'Richmond',
        ]);

        return Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => $title,
            'crew_id' => $crew->id,
            'status' => 'scheduled',
            'scheduled_date' => now()->addDay()->toDateString(),
        ]);
    }

    /** Turn the review bypass on for a test. */
    private function enableReviewAccount(): void
    {
        config([
            'app_review.email' => 'test@apple.com',
            'app_review.code' => '123456',
            'app_review.role' => 'foreman',
        ]);
    }

    public function test_the_app_review_account_signs_in_with_the_fixed_code(): void
    {
        $this->enableReviewAccount();

        $this->postJson('/api/auth/request-code', ['email' => 'test@apple.com'])->assertOk();

        // Nothing is emailed and nothing is stored for the review account.
        Mail::assertNothingSent();
        $this->assertSame(0, EmployeeLoginCode::count());

        $response = $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ]);

        $response->assertOk()->assertJsonPath('employee.role', 'foreman');
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_the_review_account_is_provisioned_on_first_use(): void
    {
        $this->enableReviewAccount();

        $this->assertSame(0, Employee::where('email', 'test@apple.com')->count());

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ])->assertOk();

        $employee = Employee::where('email', 'test@apple.com')->firstOrFail();
        $this->assertSame('active', $employee->status);
        $this->assertSame('foreman', $employee->role);
    }

    public function test_provisioning_is_idempotent(): void
    {
        $this->enableReviewAccount();

        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $this->jobFor($crew, 'Mowing');

        foreach (range(1, 3) as $ignored) {
            $this->postJson('/api/auth/verify-code', [
                'email' => 'test@apple.com',
                'code' => '123456',
            ])->assertOk();
        }

        $this->assertSame(1, Employee::where('email', 'test@apple.com')->count());
        $this->assertSame(1, CrewMember::whereIn(
            'employee_id',
            Employee::where('email', 'test@apple.com')->pluck('id')
        )->count());
    }

    public function test_the_review_account_joins_the_busiest_upcoming_crew(): void
    {
        $this->enableReviewAccount();

        $quiet = Crew::create(['name' => 'Quiet Crew', 'status' => 'active']);
        $busy = Crew::create(['name' => 'Busy Crew', 'status' => 'active']);

        $this->jobFor($quiet, 'Quiet job');
        foreach (range(1, 3) as $n) {
            $this->jobFor($busy, "Busy job {$n}");
        }

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ])->assertOk();

        $employee = Employee::where('email', 'test@apple.com')->firstOrFail();

        // Joined as a member, never as the foreman — a real crew's dispatch pin
        // must not be displaced by the demo account.
        $this->assertDatabaseHas('crew_members', [
            'employee_id' => $employee->id,
            'crew_id' => $busy->id,
        ]);
        $this->assertNull($busy->fresh()->foreman_id);
    }

    public function test_an_explicit_crew_id_wins_over_the_automatic_choice(): void
    {
        $this->enableReviewAccount();

        $chosen = Crew::create(['name' => 'Chosen Crew', 'status' => 'active']);
        $busy = Crew::create(['name' => 'Busy Crew', 'status' => 'active']);
        $this->jobFor($busy, 'Busy job');

        config(['app_review.crew_id' => $chosen->id]);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ])->assertOk();

        $this->assertDatabaseHas('crew_members', [
            'employee_id' => Employee::where('email', 'test@apple.com')->value('id'),
            'crew_id' => $chosen->id,
        ]);
    }

    public function test_the_wrong_code_is_rejected_for_the_review_account(): void
    {
        $this->enableReviewAccount();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '000000',
        ])->assertStatus(422)->assertJsonValidationErrors('code');

        // A failed attempt must not leave an account behind.
        $this->assertSame(0, Employee::where('email', 'test@apple.com')->count());
    }

    public function test_the_review_bypass_is_off_by_default(): void
    {
        // Nothing configured — the shipped default.
        config(['app_review.email' => '', 'app_review.code' => '']);

        $this->postJson('/api/auth/request-code', ['email' => 'test@apple.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ])->assertStatus(422);

        $this->assertSame(0, Employee::where('email', 'test@apple.com')->count());
    }

    public function test_the_bypass_needs_both_the_email_and_the_code_configured(): void
    {
        // Half-configured must not open the door.
        config(['app_review.email' => 'test@apple.com', 'app_review.code' => '']);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'test@apple.com',
            'code' => '123456',
        ])->assertStatus(422);

        $this->assertSame(0, Employee::where('email', 'test@apple.com')->count());
    }

    public function test_other_accounts_still_require_a_real_code_while_the_bypass_is_on(): void
    {
        $this->enableReviewAccount();
        $this->employee();

        // The review code must not work for anyone else.
        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();
        Mail::assertSent(LoginCodeMail::class);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => '123456',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    /* -------------------------------------------------------------------- */
    /* Temporary universal master code (email-outage stopgap)               */
    /* -------------------------------------------------------------------- */

    /** Simulate the mailer throwing, the way a Postmark outage would. */
    private function breakTheMailer(): void
    {
        $pending = \Mockery::mock();
        $pending->shouldReceive('send')->andThrow(new \RuntimeException('smtp down'));
        Mail::shouldReceive('to')->andReturn($pending);
    }

    public function test_the_master_code_signs_in_any_active_employee(): void
    {
        config(['app_review.master_code' => '999999']);
        $employee = $this->employee();

        $response = $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => '999999',
        ]);

        $response->assertOk()->assertJsonPath('employee.id', $employee->id);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_the_master_code_does_nothing_when_unset(): void
    {
        // Not configured — the shipped default.
        $this->employee();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => '999999',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_the_master_code_is_rejected_for_an_inactive_employee(): void
    {
        config(['app_review.master_code' => '999999']);
        $this->employee(['status' => 'terminated']);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => '999999',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_the_master_code_is_rejected_for_an_unknown_email(): void
    {
        config(['app_review.master_code' => '999999']);

        $this->postJson('/api/auth/verify-code', [
            'email' => 'nobody@marshallslawn.com',
            'code' => '999999',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_a_real_emailed_code_still_works_while_the_master_code_is_set(): void
    {
        config(['app_review.master_code' => '999999']);
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();

        // The genuine per-request code is still accepted alongside the master.
        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => $this->sentCode(),
        ])->assertOk();
    }

    public function test_a_wrong_code_is_still_rejected_while_the_master_code_is_set(): void
    {
        config(['app_review.master_code' => '999999']);
        $this->employee();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])->assertOk();

        $this->postJson('/api/auth/verify-code', [
            'email' => 'crew@marshallslawn.com',
            'code' => '111111',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_request_code_succeeds_despite_a_mailer_failure_while_the_master_code_is_set(): void
    {
        config(['app_review.master_code' => '999999']);
        $this->employee();
        $this->breakTheMailer();

        // The tester must still reach the code screen even though email is down.
        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])
            ->assertOk()
            ->assertJsonPath('expires_in_minutes', 10);
    }

    public function test_request_code_surfaces_a_mailer_failure_when_the_master_code_is_unset(): void
    {
        // Without the stopgap, a real mail outage must not be silently hidden.
        $this->employee();
        $this->breakTheMailer();

        $this->postJson('/api/auth/request-code', ['email' => 'crew@marshallslawn.com'])
            ->assertStatus(500);
    }
}
