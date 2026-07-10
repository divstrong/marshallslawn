<?php

namespace Tests\Feature;

use App\Models\Crew;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Job;
use App\Models\Property;
use App\Services\Translation\TranslationDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiJobTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deterministic fake driver: "ES:<text>".
        $this->app->instance(TranslationDriver::class, new FakeTranslationDriver());
    }

    private function foremanWithJob(): array
    {
        $employee = Employee::create([
            'name' => 'Carlos Foreman',
            'first_name' => 'Carlos',
            'last_name' => 'Foreman',
            'role' => 'foreman',
            'status' => 'active',
        ]);
        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active', 'foreman_id' => $employee->id]);

        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);

        $job = Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'crew_id' => $crew->id,
            'title' => 'Mow the front lawn',
            'description' => 'Bag the clippings',
            'status' => 'scheduled',
            'scheduled_date' => '2026-07-15',
        ]);

        return [$employee, $job];
    }

    public function test_jobs_endpoint_translates_details_when_app_language_is_spanish(): void
    {
        [$employee, $job] = $this->foremanWithJob();
        Sanctum::actingAs($employee);

        $this->getJson('/api/jobs', ['X-App-Language' => 'es'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'ES:Mow the front lawn'])
            ->assertJsonFragment(['description' => 'ES:Bag the clippings']);
    }

    public function test_jobs_endpoint_returns_english_untouched_by_default(): void
    {
        [$employee, $job] = $this->foremanWithJob();
        Sanctum::actingAs($employee);

        $this->getJson('/api/jobs', ['X-App-Language' => 'en'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Mow the front lawn']);
    }
}
