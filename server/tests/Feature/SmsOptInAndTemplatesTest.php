<?php

namespace Tests\Feature;

use App\Livewire\SmsTemplateManager;
use App\Models\Customer;
use App\Models\Role;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SmsOptInAndTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_opt_in_page_renders_the_consent_language(): void
    {
        $this->get('/sms-opt-in')
            ->assertOk()
            ->assertSee('Consent is not a condition', false)
            ->assertSee('Reply STOP to unsubscribe', false);
    }

    public function test_submitting_without_consent_stores_nothing(): void
    {
        $this->post('/sms-opt-in', [
            'first_name' => 'No',
            'last_name' => 'Consent',
            'phone' => '8045550000',
            // consent unchecked
        ])->assertRedirect();

        $this->assertDatabaseMissing('customers', ['last_name' => 'Consent']);
    }

    public function test_submitting_with_consent_records_a_pending_customer(): void
    {
        $this->post('/sms-opt-in', [
            'first_name' => 'Opted',
            'last_name' => 'In',
            'phone' => '8045550001',
            'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'last_name' => 'In',
            'sms_consent_status' => Customer::SMS_PENDING,
        ]);
    }

    public function test_the_honeypot_silently_drops_bot_submissions(): void
    {
        $this->post('/sms-opt-in', [
            'first_name' => 'Bot',
            'last_name' => 'Spam',
            'phone' => '8045550002',
            'consent' => '1',
            'website' => 'http://spam.example',
        ])->assertRedirect();

        $this->assertDatabaseMissing('customers', ['last_name' => 'Spam']);
    }

    public function test_render_returns_null_for_an_inactive_template(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => false]);
        $this->assertNull(SmsTemplate::render('job_scheduled', ['name' => 'Jane']));
    }

    public function test_render_substitutes_placeholders_for_an_active_template(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update([
            'is_active' => true,
            'body' => 'Hi {name}, your {service} is on {date}.',
        ]);

        $this->assertSame(
            'Hi Jane, your Mowing is on Mon.',
            SmsTemplate::render('job_scheduled', ['name' => 'Jane', 'service' => 'Mowing', 'date' => 'Mon']),
        );
    }

    public function test_the_notifications_tab_toggles_a_template_active(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $template = SmsTemplate::where('key', 'invoice_issued')->first();
        $this->assertFalse($template->is_active);

        Livewire::test(SmsTemplateManager::class)
            ->call('toggle', $template->id);

        $this->assertTrue($template->fresh()->is_active);
    }
}
