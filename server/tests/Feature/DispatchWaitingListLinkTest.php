<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchWaitingListLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dispatch_header_links_to_the_waiting_list_in_a_new_tab(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->get('/dispatch')
            ->assertOk()
            ->assertSee('Waiting List')
            ->assertSee(route('filament.admin.resources.jobs.waiting-list'), false)
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
}
