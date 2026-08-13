<?php

namespace Tests\Feature;

use App\Livewire\InvoiceBuilder;
use App\Livewire\SettingsTerms;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Service;
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

    /**
     * Invoices are built through InvoiceBuilder now (see InvoiceBuilderTest for the
     * line/discount/credit maths); this covers only the numbering, which the model
     * assigns on create.
     */
    public function test_invoice_numbers_are_assigned_and_never_collide(): void
    {
        $service = Service::create([
            'name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 60, 'is_active' => true,
        ]);

        foreach ([1, 2] as $ignored) {
            Livewire::test(InvoiceBuilder::class)
                ->call('selectCustomer', $this->customer->id)
                ->call('addService', $service->id)
                ->call('save')
                ->assertHasNoErrors();
        }

        $numbers = Invoice::pluck('invoice_number');
        $this->assertCount(2, $numbers);
        $this->assertCount(2, $numbers->unique(), 'numbers must not collide');
        $this->assertNotContains(null, $numbers->all());
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^INV-\d{5}$/', $number);
        }
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
