<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Livewire\SettingsTerms;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceFormAndTermsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create(['first_name' => 'Otto', 'last_name' => 'Kerr', 'status' => 'active']);
    }

    public function test_creating_an_invoice_assigns_a_number_and_links_the_estimate(): void
    {
        $estimate = Estimate::create([
            'customer_id' => $this->customer->id,
            'estimate_number' => 'EST-900',
            'share_token' => 'tok900',
            'status' => 'accepted',
            'subtotal' => 300,
            'total' => 300,
        ]);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'estimate_id' => $estimate->id,
                'status' => 'draft',
                'subtotal' => 300,
                'tax' => 24,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::sole();
        $this->assertMatchesRegularExpression('/^INV-\d{5}$/', $invoice->invoice_number);
        $this->assertSame($estimate->id, $invoice->estimate_id);
        $this->assertSame(324.0, (float) $invoice->total);
        $this->assertTrue($invoice->allows_payment_plan, 'plans are allowed unless switched off');
    }

    public function test_the_invoice_number_is_never_blank_even_across_several_creates(): void
    {
        foreach ([100, 200] as $amount) {
            Livewire::test(CreateInvoice::class)
                ->fillForm([
                    'customer_id' => $this->customer->id,
                    'status' => 'draft',
                    'subtotal' => $amount,
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $numbers = Invoice::pluck('invoice_number');
        $this->assertCount(2, $numbers);
        $this->assertCount(2, $numbers->unique(), 'numbers must not collide');
        $this->assertNotContains(null, $numbers->all());
    }

    private function publicInvoice(array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'customer_id' => $this->customer->id,
            'share_token' => 'pubtok',
            'status' => 'sent',
            'subtotal' => 480,
            'total' => 480,
            'issued_at' => now()->subDays(3),
            'due_at' => now()->addDays(27),
        ], $attributes));
    }

    public function test_the_public_invoice_shows_the_invoice_terms_from_settings(): void
    {
        Setting::updateOrCreate(
            ['key' => SettingsTerms::INVOICE_SETTING_KEY],
            ['value' => 'Net 15. Late accounts accrue 1.5% monthly.', 'group' => 'terms'],
        );

        $this->get('/invoice/' . $this->publicInvoice()->share_token)
            ->assertOk()
            ->assertSee('Payment Terms')
            ->assertSee('Net 15. Late accounts accrue 1.5% monthly.');
    }

    public function test_the_default_invoice_terms_show_when_none_are_saved(): void
    {
        $this->get('/invoice/' . $this->publicInvoice()->share_token)
            ->assertOk()
            ->assertSee('Payment is due within 30 days of the invoice date');
    }

    public function test_the_payment_plan_option_is_hidden_when_the_invoice_disallows_it(): void
    {
        $allowed = $this->publicInvoice(['allows_payment_plan' => true]);
        $this->get('/invoice/' . $allowed->share_token)
            ->assertOk()
            ->assertSee('Split into 12 monthly payments');

        $allowed->update(['allows_payment_plan' => false]);
        $this->get('/invoice/' . $allowed->share_token)
            ->assertOk()
            ->assertDontSee('Split into 12 monthly payments');
    }

    public function test_a_forged_payment_plan_request_is_ignored_when_plans_are_disallowed(): void
    {
        $invoice = $this->publicInvoice(['allows_payment_plan' => false]);

        // The gateway isn't configured under test, so the charge fails and we're
        // redirected — the point is that the plan branch was never entered.
        $this->post('/invoice/' . $invoice->share_token . '/pay', [
            'source_token' => 'tok_fake',
            'payment_type' => 'card',
            'payment_plan' => '1',
        ]);

        $invoice->refresh();
        $this->assertFalse((bool) $invoice->is_payment_plan, 'no plan may be started on this invoice');
        $this->assertNotSame('payment_plan', $invoice->status);
    }
}
