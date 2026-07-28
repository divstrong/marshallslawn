<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateEmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        filament()->setCurrentPanel('admin');

        // The role assigned to the new employee must exist as a Role row for
        // the form's Select to accept it.
        Role::firstOrCreate(['name' => 'estimator'], ['label' => 'Estimator']);
    }

    /**
     * Reproduces the production 23000 error: creating an employee while leaving
     * the optional pay/contact fields blank. Filament submits those empty
     * fields as explicit NULLs, so `hourly_rate` must tolerate a null insert.
     */
    public function test_an_employee_can_be_created_without_an_hourly_rate(): void
    {
        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'first_name' => 'Jim',
                'last_name' => 'Doyle',
                'email' => 'jdoyle@divstrong.com',
                'role' => 'estimator',
                'status' => 'active',
                // hourly_rate, phones, address, dates all deliberately left blank.
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::where('email', 'jdoyle@divstrong.com')->firstOrFail();
        $this->assertNull($employee->hourly_rate);
        $this->assertSame('Jim Doyle', $employee->name);
        $this->assertSame('estimator', $employee->role);
    }

    public function test_an_hourly_rate_is_still_saved_when_provided(): void
    {
        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'first_name' => 'Pat',
                'last_name' => 'Rate',
                'email' => 'pat@divstrong.com',
                'role' => 'estimator',
                'status' => 'active',
                'hourly_rate' => '28.50',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('28.50', Employee::where('email', 'pat@divstrong.com')->value('hourly_rate'));
    }
}
