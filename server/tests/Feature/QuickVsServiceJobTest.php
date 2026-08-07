<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource\Pages\CreateJob;
use App\Livewire\JobServiceLines;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickVsServiceJobTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        filament()->setCurrentPanel('admin');

        $this->customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $this->property = Property::create(['customer_id' => $this->customer->id, 'address' => '1 Elm St']);
    }

    public function test_the_job_type_decides_which_pricing_the_form_offers(): void
    {
        // Service: build a scope on the Services tab, no flat price field.
        $service = Livewire::test(CreateJob::class)->fillForm(['kind' => Job::KIND_SERVICE]);
        $service->assertFormFieldHidden('price', 'form');
        $this->assertStringContainsString('Services', $service->html());

        // Quick: one flat price, and the Services tab is gone entirely.
        $quick = Livewire::test(CreateJob::class)->fillForm(['kind' => Job::KIND_QUICK]);
        $quick->assertFormFieldVisible('price', 'form');
        $this->assertStringNotContainsString('Services', $quick->html());
    }

    public function test_a_quick_job_is_priced_by_its_flat_price(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'kind' => Job::KIND_QUICK,
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'price' => 250,
                'notes' => "Clear the storm debris\nGate code 1234",
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $job = Job::sole();

        $this->assertSame(Job::KIND_QUICK, $job->kind);
        $this->assertSame(250.0, $job->total());
        $this->assertCount(0, $job->jobServices);
        // Its label is the first line of the notes.
        $this->assertSame('Clear the storm debris', $job->title);
    }

    public function test_a_quick_job_without_notes_still_gets_a_label(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'kind' => Job::KIND_QUICK,
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'price' => 80,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Quick job', Job::sole()->title);
    }

    public function test_a_quick_job_ignores_any_buffered_service_lines(): void
    {
        $service = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'is_active' => true]);

        $component = Livewire::test(CreateJob::class)
            ->fillForm([
                'kind' => Job::KIND_QUICK,
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'price' => 90,
            ]);

        // Switching to Quick after touching the Services grid must not smuggle
        // the buffered lines through.
        \Illuminate\Support\Facades\Cache::put(
            JobServiceLines::draftCacheKey((string) $component->get('data.services_draft_id')),
            [['service_id' => $service->id, 'description' => 'Mowing', 'quantity' => 1, 'unit_price' => 45, 'price' => 45]],
            now()->addMinutes(60),
        );

        $component->call('create')->assertHasNoFormErrors();

        $job = Job::sole();
        $this->assertCount(0, $job->jobServices);
        $this->assertSame(90.0, $job->total());
    }

    public function test_a_quick_jobs_label_follows_its_notes(): void
    {
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'kind' => Job::KIND_QUICK,
            'status' => 'pending',
            'notes' => 'Original note',
        ]);

        $this->assertSame('Original note', $job->title);

        $job->update(['notes' => 'Rewritten note']);

        $this->assertSame('Rewritten note', $job->fresh()->title);
    }

    public function test_a_service_jobs_label_follows_its_lines(): void
    {
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'is_active' => true]);
        $mulch = Service::create(['name' => 'Mulch', 'category' => 'Lawn', 'is_active' => true]);

        $job = Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'kind' => Job::KIND_SERVICE,
            'status' => 'pending',
        ]);

        $this->assertSame('Service job', $job->title, 'a service job with no lines still has a label');

        $job->jobServices()->create(['service_id' => $mowing->id, 'quantity' => 1, 'sort_order' => 0]);
        $job->refreshTitle();
        $this->assertSame('Mowing', $job->fresh()->title);

        $job->jobServices()->create(['service_id' => $mulch->id, 'quantity' => 1, 'sort_order' => 1]);
        $job->refreshTitle();
        $this->assertSame('Mowing +1 more', $job->fresh()->title);
    }

    public function test_editing_the_services_grid_retitles_the_job(): void
    {
        $service = Service::create(['name' => 'Leaf Removal', 'category' => 'Lawn', 'is_active' => true]);
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'kind' => Job::KIND_SERVICE,
            'status' => 'pending',
        ]);

        Livewire::test(JobServiceLines::class, ['jobId' => $job->id])
            ->call('addService', $service->id);

        $this->assertSame('Leaf Removal', $job->fresh()->title);
    }

    public function test_a_quick_job_created_from_scheduling_is_flagged_as_such(): void
    {
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'kind' => Job::KIND_QUICK,
            'price' => 60,
            'status' => 'scheduled',
            'scheduled_date' => '2026-08-20',
        ]);

        $this->assertTrue($job->isQuick());
        $this->assertSame(60.0, $job->total());
    }
}
