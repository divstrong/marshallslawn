<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource;
use App\Filament\Resources\JobResource\Pages\ListWaitingListJobs;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JobDuplicateAndWaitingListTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $property;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $this->property = Property::create(['customer_id' => $this->customer->id, 'address' => '1 Elm St']);
        $this->service = Service::create(['name' => 'Mulch', 'category' => 'lawn', 'is_active' => true]);
    }

    private function makeJob(array $attributes = []): Job
    {
        return Job::create(array_merge([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'title' => 'Mulch job',
            'status' => 'pending',
        ], $attributes));
    }

    public function test_the_waiting_list_view_shows_only_waiting_list_jobs(): void
    {
        $waiting = $this->makeJob(['title' => 'Parked work', 'status' => 'waiting_list']);
        $booked = $this->makeJob(['title' => 'Booked work', 'status' => 'scheduled']);

        $this->get(JobResource::getUrl('waiting-list'))->assertOk();

        Livewire::test(ListWaitingListJobs::class)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$booked]);
    }

    public function test_duplicating_a_job_copies_its_service_lines(): void
    {
        $job = $this->makeJob();
        $job->jobServices()->create([
            'service_id' => $this->service->id,
            'quantity' => 2,
            'unit_price' => 25,
            'price' => 50,
            'description' => 'Double shred',
            'sort_order' => 0,
        ]);

        $copy = JobResource::duplicateJob($job->fresh());

        $this->assertNotSame($job->id, $copy->id);
        $this->assertSame('Mulch job', $copy->title);
        $this->assertCount(1, $copy->jobServices);
        $this->assertSame('Double shred', $copy->jobServices->first()->description);
        $this->assertEquals(50, $copy->jobServices->first()->price);
    }

    public function test_a_duplicate_starts_fresh_and_never_clones_a_series(): void
    {
        $job = $this->makeJob([
            'status' => 'completed',
            'type' => 'recurring',
            'scheduled_date' => '2026-01-01',
            'completed_date' => '2026-01-02',
        ]);

        $copy = JobResource::duplicateJob($job->fresh(), '2026-03-04');

        $this->assertSame('pending', $copy->status);
        $this->assertSame('one_time', $copy->type);
        $this->assertNull($copy->recurring_job_template_id);
        $this->assertNull($copy->completed_date);
        $this->assertSame('2026-03-04', $copy->scheduled_date->toDateString());
    }

    public function test_a_duplicate_with_no_date_is_created_unscheduled(): void
    {
        $copy = JobResource::duplicateJob($this->makeJob(['scheduled_date' => '2026-01-01']));

        $this->assertNull($copy->scheduled_date);
    }
}
