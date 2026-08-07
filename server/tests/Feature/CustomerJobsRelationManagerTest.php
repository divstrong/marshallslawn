<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\JobsRelationManager;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerJobsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_job_modal_creates_a_job_pinned_to_the_owning_customer(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        filament()->setCurrentPanel('admin');

        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);

        // Services flow through the shared JobServiceLines grid + draft cache (covered
        // by CreateJobServicesTabTest / JobServiceLinesTest); here we verify the modal
        // reuses the main form and pins the job to the tab's owner.
        Livewire::test(JobsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            // Only the property is supplied: the modal pre-fills the customer and
            // the form's defaults (job type, status, priority, frequency).
            ->callTableAction('create', data: ['property_id' => $property->id])
            ->assertHasNoTableActionErrors();

        $job = Job::sole();
        $this->assertSame($customer->id, $job->customer_id);
    }
}
