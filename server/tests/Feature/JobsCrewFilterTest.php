<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource\Pages\ListJobs;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JobsCrewFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_crew_filter_narrows_the_table_to_the_ticked_crews(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $customer = Customer::create(['first_name' => 'Ivy', 'last_name' => 'Rand', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '3 Fern Way']);

        $crewA = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $crewB = Crew::create(['name' => 'Crew B', 'status' => 'active']);
        $crewC = Crew::create(['name' => 'Crew C', 'status' => 'active']);

        $make = fn (?Crew $crew, string $title): Job => Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => $title,
            'status' => 'scheduled',
            'crew_id' => $crew?->id,
            'scheduled_date' => '2026-08-12',
        ]);

        $jobA = $make($crewA, 'Mow A');
        $jobB = $make($crewB, 'Mow B');
        $jobC = $make($crewC, 'Mow C');
        $unassigned = $make(null, 'Mow none');

        $component = Livewire::test(ListJobs::class);

        // No filter: everything shows.
        $component->assertCanSeeTableRecords([$jobA, $jobB, $jobC, $unassigned]);

        // Two crews ticked: only their jobs survive, including neither unassigned
        // nor the untouched third crew.
        $component
            ->filterTable('crew_id', [$crewA->id, $crewB->id])
            ->assertCanSeeTableRecords([$jobA, $jobB])
            ->assertCanNotSeeTableRecords([$jobC, $unassigned]);

        // Untick back to one crew.
        $component
            ->filterTable('crew_id', [$crewC->id])
            ->assertCanSeeTableRecords([$jobC])
            ->assertCanNotSeeTableRecords([$jobA, $jobB]);

        // Clearing shows everything again.
        $component
            ->filterTable('crew_id', [])
            ->assertCanSeeTableRecords([$jobA, $jobB, $jobC, $unassigned]);
    }
}
