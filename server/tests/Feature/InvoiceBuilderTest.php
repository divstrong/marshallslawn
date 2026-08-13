<?php

namespace Tests\Feature;

use App\Livewire\InvoiceBuilder;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Service $mowing;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create(['first_name' => 'Iris', 'last_name' => 'Vance', 'status' => 'active']);
        $this->mowing = Service::create([
            'name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 60, 'is_active' => true,
        ]);
    }

    public function test_totals_are_derived_from_the_lines_as_they_are_entered(): void
    {
        $component = Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addService', $this->mowing->id);

        // The service's default rate seeds the line.
        $this->assertSame(60.0, $component->instance()->servicesTotal);
        $this->assertSame(60.0, $component->instance()->grandTotal);

        // Quantity drives the line: 3 × 60.
        $component->set('lines.0.quantity', '3');
        $this->assertSame(180.0, $component->instance()->servicesTotal);

        // A discount comes off the subtotal, tax is added, a credit comes off after.
        $component
            ->call('addDiscount')
            ->set('discounts.0.amount', '30')
            ->set('tax', '12.50')
            ->call('addCredit')
            ->set('credits.0.description', 'Referral credit')
            ->set('credits.0.amount', '20');

        $instance = $component->instance();
        $this->assertSame(150.0, $instance->subtotal, 'services less discounts');
        $this->assertSame(12.5, $instance->taxAmount);
        $this->assertSame(20.0, $instance->creditsTotal);
        $this->assertSame(142.5, $instance->grandTotal, '150 + 12.50 − 20');
    }

    public function test_the_preview_shows_the_running_figures(): void
    {
        Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addService', $this->mowing->id)
            ->set('lines.0.quantity', '2')
            ->assertSee('Iris Vance')
            ->assertSee('Amount due')
            ->assertSee('$120.00')
            ->assertSee('Number assigned on save');
    }

    public function test_saving_persists_the_lines_discounts_and_credits_with_computed_totals(): void
    {
        Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addService', $this->mowing->id)
            ->set('lines.0.quantity', '2')
            ->call('addCustomLine')
            ->set('lines.1.description', 'Haul away clippings')
            ->set('lines.1.quantity', '1')
            ->set('lines.1.unit_price', '45')
            ->call('addDiscount')
            ->set('discounts.0.description', 'Spring promo')
            ->set('discounts.0.amount', '15')
            ->call('addCredit')
            ->set('credits.0.code', 'REF10')
            ->set('credits.0.description', 'Referral credit')
            ->set('credits.0.amount', '10')
            ->set('tax', '5')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = Invoice::sole();
        $this->assertMatchesRegularExpression('/^INV-\d{5}$/', $invoice->invoice_number);
        // 120 + 45 = 165 services, less the 15 discount.
        $this->assertSame(150.0, (float) $invoice->subtotal);
        $this->assertSame(10.0, (float) $invoice->credits_total);
        $this->assertSame(145.0, (float) $invoice->total, '150 + 5 tax − 10 credit');

        // Three line items: two services plus the discount as a negative line.
        $lines = $invoice->lineItems()->orderBy('sort_order')->get();
        $this->assertCount(3, $lines);
        $this->assertSame(120.0, (float) $lines[0]->total);
        $this->assertSame($this->mowing->id, $lines[0]->service_id);
        $this->assertSame(45.0, (float) $lines[1]->total);
        $this->assertSame(-15.0, (float) $lines[2]->total, 'a discount is stored as a negative line');

        $credit = $invoice->credits()->sole();
        $this->assertSame('REF10', $credit->code);
        $this->assertSame(10.0, (float) $credit->amount);
    }

    public function test_an_invoice_cannot_be_saved_without_a_customer_or_any_lines(): void
    {
        Livewire::test(InvoiceBuilder::class)
            ->call('save')
            ->assertHasErrors(['customerId', 'lines']);

        $this->assertSame(0, Invoice::count());
    }

    public function test_a_line_needs_a_description(): void
    {
        Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addCustomLine')
            ->set('lines.0.unit_price', '30')
            ->call('save')
            ->assertHasErrors(['lines.0.description']);

        $this->assertSame(0, Invoice::count());
    }

    public function test_adjustments_bigger_than_the_work_are_refused(): void
    {
        Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addService', $this->mowing->id)
            ->call('addDiscount')
            ->set('discounts.0.description', 'Goodwill')
            ->set('discounts.0.amount', '500')
            ->call('save')
            ->assertHasErrors('discounts');

        $this->assertSame(0, Invoice::count(), 'a negative total is a refund, not an invoice');
    }

    public function test_lines_can_be_copied_in_from_a_linked_estimate(): void
    {
        $estimate = Estimate::create([
            'customer_id' => $this->customer->id,
            'estimate_number' => 'EST-777',
            'share_token' => 'tok777',
            'status' => 'accepted',
            'subtotal' => 200,
            'tax' => 8,
            'total' => 208,
        ]);
        EstimateLineItem::create([
            'estimate_id' => $estimate->id,
            'service_id' => $this->mowing->id,
            'description' => 'Seasonal mowing',
            'quantity' => 4,
            'unit_price' => 50,
            'total' => 200,
            'sort_order' => 0,
        ]);

        $component = Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('selectEstimate', $estimate->id)
            ->assertSet('estimateId', $estimate->id)
            ->call('importFromEstimate');

        $this->assertSame(200.0, $component->instance()->servicesTotal);
        $this->assertSame(8.0, $component->instance()->taxAmount, 'the estimate tax comes across too');
        $component->assertSet('lines.0.description', 'Seasonal mowing');

        $component->call('save')->assertHasNoErrors();

        $invoice = Invoice::sole();
        $this->assertSame($estimate->id, $invoice->estimate_id);
        $this->assertSame(208.0, (float) $invoice->total);
    }

    public function test_another_customers_estimate_is_not_imported(): void
    {
        $other = Customer::create(['first_name' => 'Cal', 'last_name' => 'Reed', 'status' => 'active']);
        $estimate = Estimate::create([
            'customer_id' => $other->id,
            'estimate_number' => 'EST-888',
            'share_token' => 'tok888',
            'status' => 'sent',
            'subtotal' => 90,
            'total' => 90,
        ]);
        EstimateLineItem::create([
            'estimate_id' => $estimate->id,
            'description' => 'Not ours',
            'quantity' => 1,
            'unit_price' => 90,
            'total' => 90,
            'sort_order' => 0,
        ]);

        $component = Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->set('estimateId', $estimate->id)
            ->call('importFromEstimate');

        $this->assertSame(0.0, $component->instance()->servicesTotal);
    }

    public function test_switching_customer_drops_a_linked_estimate(): void
    {
        $other = Customer::create(['first_name' => 'Cal', 'last_name' => 'Reed', 'status' => 'active']);
        $estimate = $this->estimateFor($this->customer, 'EST-999', 50);

        Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('selectEstimate', $estimate->id)
            ->call('selectCustomer', $other->id)
            ->assertSet('estimateId', null)
            ->assertSet('estimateSearch', '');
    }

    public function test_the_estimate_picker_lists_the_newest_first_and_can_be_searched(): void
    {
        // Created out of order on purpose: the list must follow created_at, not id.
        $oldest = $this->estimateFor($this->customer, 'EST-100', 100, now()->subYear());
        $newest = $this->estimateFor($this->customer, 'EST-300', 300, now()->subDay());
        $middle = $this->estimateFor($this->customer, 'EST-200', 200, now()->subMonths(3));

        $component = Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id);

        $numbers = $component->instance()->estimateResults->pluck('estimate_number')->all();
        $this->assertSame(['EST-300', 'EST-200', 'EST-100'], $numbers, 'newest created_at first');

        // Searching by number narrows the list.
        $component->set('estimateSearch', '200');
        $this->assertSame(
            ['EST-200'],
            $component->instance()->estimateResults->pluck('estimate_number')->all(),
        );

        // Searching by status works too.
        $middle->update(['status' => 'declined']);
        $component->set('estimateSearch', 'declined');
        $this->assertSame(
            ['EST-200'],
            $component->instance()->estimateResults->pluck('estimate_number')->all(),
        );

        // And a search matching nothing returns nothing rather than everything.
        $component->set('estimateSearch', 'zzzzz');
        $this->assertCount(0, $component->instance()->estimateResults);
    }

    public function test_the_estimate_picker_only_offers_the_chosen_customers_estimates(): void
    {
        $mine = $this->estimateFor($this->customer, 'EST-MINE', 10);
        $other = Customer::create(['first_name' => 'Cal', 'last_name' => 'Reed', 'status' => 'active']);
        $theirs = $this->estimateFor($other, 'EST-THEIRS', 20);

        $component = Livewire::test(InvoiceBuilder::class)
            ->call('selectCustomer', $this->customer->id);

        $this->assertSame(
            ['EST-MINE'],
            $component->instance()->estimateResults->pluck('estimate_number')->all(),
        );

        // Selecting another customer's estimate by id is refused outright.
        $component->call('selectEstimate', $theirs->id)->assertSet('estimateId', null);
    }

    private function estimateFor(Customer $customer, string $number, float $total, ?\Carbon\Carbon $createdAt = null): Estimate
    {
        $estimate = Estimate::create([
            'customer_id' => $customer->id,
            'estimate_number' => $number,
            'share_token' => substr(md5($number), 0, 6),
            'status' => 'sent',
            'subtotal' => $total,
            'total' => $total,
        ]);

        if ($createdAt) {
            $estimate->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $estimate->fresh();
    }

    public function test_the_builder_opens_pointed_at_a_customer_passed_in(): void
    {
        Livewire::test(InvoiceBuilder::class, ['customerId' => $this->customer->id])
            ->assertSet('customerId', $this->customer->id)
            ->assertSee('Iris Vance');
    }
}
