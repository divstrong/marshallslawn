<?php

namespace Tests\Feature;

use App\Livewire\JobServiceLines;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class JobServiceLinesTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $name, ?float $price): Service
    {
        return Service::create(['name' => $name, 'category' => 'Lawn', 'default_price' => $price, 'is_active' => true]);
    }

    private function job(): Job
    {
        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);

        return Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => 'Job',
            'status' => 'pending',
        ]);
    }

    public function test_draft_mode_mirrors_rows_into_the_cache_without_a_job(): void
    {
        $mow = $this->service('Mowing', 45);
        $mulch = $this->service('Mulch', null); // no default rate -> TBD

        Livewire::test(JobServiceLines::class, ['draftId' => 'draft-1'])
            ->call('addService', $mow->id)
            ->call('addService', $mulch->id)
            ->set('lines.0.quantity', '3');

        $cached = Cache::get(JobServiceLines::draftCacheKey('draft-1'));
        $this->assertCount(2, $cached);

        // Mowing: qty 3 × 45 = 135.
        $this->assertSame(45.0, (float) $cached[0]['unit_price']);
        $this->assertSame(135.0, (float) $cached[0]['price']);

        // Mulch: no rate -> TBD (null unit_price + price).
        $this->assertNull($cached[1]['unit_price']);
        $this->assertNull($cached[1]['price']);

        // No JobService rows are written in draft mode.
        $this->assertDatabaseCount('job_services', 0);
    }

    public function test_edit_mode_persists_rows_to_the_job(): void
    {
        $job = $this->job();
        $mow = $this->service('Mowing', 50);

        $component = Livewire::test(JobServiceLines::class, ['jobId' => $job->id])
            ->call('addService', $mow->id);

        $this->assertDatabaseHas('job_services', [
            'job_id' => $job->id,
            'service_id' => $mow->id,
            'quantity' => 1,
            'unit_price' => 50,
            'price' => 50,
        ]);

        // Change quantity -> total recomputes and persists.
        $component->set('lines.0.quantity', '2');
        $this->assertSame('100.00', (string) $job->jobServices()->first()->price);

        // Blank the rate -> TBD.
        $component->set('lines.0.unit_price', '');
        $this->assertNull($job->jobServices()->first()->price);

        // Remove.
        $component->call('removeLine', 0);
        $this->assertDatabaseCount('job_services', 0);
    }

    public function test_a_service_with_no_default_rate_starts_as_tbd(): void
    {
        $job = $this->job();
        $tbdService = $this->service('Consult', null);

        Livewire::test(JobServiceLines::class, ['jobId' => $job->id])
            ->call('addService', $tbdService->id);

        $this->assertNull($job->jobServices()->first()->price, 'a rate-less service should be TBD, not $0');
    }
}
