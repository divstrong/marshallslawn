<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource;
use App\Filament\Resources\JobResource\Pages\ListJobs;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchWaitingListLinkTest extends TestCase
{
    use RefreshDatabase;

    /** The Jobs list, pre-filtered to the Waiting List status. */
    private function waitingListUrl(): string
    {
        return JobResource::getUrl('index', [
            'tableFilters' => ['status' => ['value' => 'waiting_list']],
        ]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_the_dispatch_header_links_to_the_filtered_jobs_list_in_a_new_tab(): void
    {
        $this->actingAs($this->admin());

        $this->get('/dispatch')
            ->assertOk()
            ->assertSee('Waiting List')
            // Points at the Jobs list with the status filter applied, not the
            // retired standalone page.
            ->assertSee($this->waitingListUrl(), false)
            ->assertSee('target="_blank"', false)
            // Still sits beside the existing escape hatch.
            ->assertSee('Back to Admin');
    }

    public function test_the_link_is_hidden_from_a_user_without_jobs_access(): void
    {
        // Dispatch access but no Jobs access: the link would only 403 on click.
        $role = Role::create(['name' => 'dispatcher', 'label' => 'Dispatcher', 'is_admin' => false]);
        RolePermission::create(['role_id' => $role->id, 'resource' => 'Dispatch']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/dispatch')
            ->assertOk()
            ->assertDontSee('Waiting List')
            ->assertSee('Back to Admin');
    }

    public function test_the_jobs_list_retitles_itself_to_the_active_status_filter(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListJobs::class)
            ->assertOk()
            // No filter: the plain resource title.
            ->assertSee('Jobs')
            ->set('tableFilters.status.value', 'waiting_list')
            ->assertSee('Jobs — Waiting List');
    }

    public function test_the_unfiltered_jobs_list_keeps_its_plain_title(): void
    {
        $this->actingAs($this->admin());

        $this->assertSame('Jobs', Livewire::test(ListJobs::class)->instance()->getTitle());
    }

    public function test_the_filtered_list_only_shows_waiting_list_jobs(): void
    {
        $this->actingAs($this->admin());

        $customer = Customer::create([
            'first_name' => 'Ada',
            'last_name' => 'Byron',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);

        $property = Property::create(['customer_id' => $customer->id, 'address' => '3 Fern Way']);

        $waiting = Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => 'Waiting job',
            'status' => 'waiting_list',
        ]);

        $scheduled = Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => 'Scheduled job',
            'status' => 'scheduled',
        ]);

        Livewire::test(ListJobs::class)
            ->set('tableFilters.status.value', 'waiting_list')
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$scheduled]);
    }

    public function test_the_retired_waiting_list_url_redirects_to_the_filtered_list(): void
    {
        $this->actingAs($this->admin());

        $this->get(JobResource::getUrl('waiting-list'))
            ->assertRedirect($this->waitingListUrl());
    }
}
