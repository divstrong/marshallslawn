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

    public function test_new_job_modal_creates_a_job_for_the_owning_customer_with_services(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        filament()->setCurrentPanel('admin');

        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);

        Livewire::test(JobsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callTableAction('create', data: [
                'title' => 'From the customer tab',
                'property_id' => $property->id,
                'status' => 'pending',
                'job_type' => 'one_time',
                'service_lines' => [
                    ['service_id' => $mowing->id, 'pricing' => 'tbd', 'description' => null],
                ],
            ])
            ->assertHasNoTableActionErrors();

        $job = Job::firstWhere('title', 'From the customer tab');
        $this->assertNotNull($job);
        $this->assertSame($customer->id, $job->customer_id);
        $this->assertSame(1, $job->jobServices()->count());
        $this->assertNull($job->jobServices()->first()->price, 'TBD line should keep a null price');
    }
}
