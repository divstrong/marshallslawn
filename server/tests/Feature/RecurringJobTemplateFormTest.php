<?php

namespace Tests\Feature;

use App\Filament\Resources\RecurringJobTemplateResource\Pages\EditRecurringJobTemplate;
use App\Models\Customer;
use App\Models\Property;
use App\Models\RecurringJobTemplate;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringJobTemplateFormTest extends TestCase
{
    use RefreshDatabase;

    private function template(int $intervalDays): RecurringJobTemplate
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);
        $service = Service::create(['name' => 'Mow', 'category' => 'lawn', 'is_active' => true]);

        return RecurringJobTemplate::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'service_id' => $service->id,
            'title' => 'Weekly mow',
            'interval_days' => $intervalDays,
            'start_date' => '2026-01-01',
            'active' => true,
        ]);
    }

    public function test_an_off_preset_interval_opens_the_custom_box(): void
    {
        $template = $this->template(45);

        Livewire::test(EditRecurringJobTemplate::class, ['record' => $template->getKey()])
            ->assertSchemaStateSet([
                'interval_preset' => 0,
                'interval_days' => 45,
            ]);
    }

    public function test_switching_to_a_preset_saves_that_interval(): void
    {
        $template = $this->template(45);

        Livewire::test(EditRecurringJobTemplate::class, ['record' => $template->getKey()])
            ->fillForm(['interval_preset' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(7, (int) $template->fresh()->interval_days);
    }
}
