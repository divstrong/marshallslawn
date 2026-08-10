<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerOverviewPageTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create([
            'first_name' => 'Marcia',
            'last_name' => 'Holt',
            'email' => 'marcia@example.com',
            'phone' => '804-555-0142',
            'address' => '88 Cedar Ln',
            'city' => 'Richmond',
            'state' => 'VA',
            'zip' => '23220',
            'status' => 'active',
            'customer_type' => 'Residential',
        ]);
    }

    public function test_the_overview_summarises_revenue_jobs_and_upcoming_work(): void
    {
        $primary = Property::create(['customer_id' => $this->customer->id, 'address' => '88 Cedar Ln', 'city' => 'Richmond', 'is_primary' => true]);
        Property::create(['customer_id' => $this->customer->id, 'address' => '12 Rear Lot', 'city' => 'Richmond']);

        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $service = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);

        // Two completed jobs worth 150 + 90 = 240 lifetime.
        foreach ([150, 90] as $i => $amount) {
            $job = Job::create([
                'customer_id' => $this->customer->id,
                'property_id' => $primary->id,
                'status' => 'completed',
                'type' => 'one_time',
                'completed_date' => now()->subDays(10 + $i)->toDateString(),
            ]);
            $job->jobServices()->create(['service_id' => $service->id, 'price' => $amount]);
        }

        // One booked ahead, one still without a date.
        Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $primary->id,
            'status' => 'pending',
            'type' => 'one_time',
            'crew_id' => $crew->id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
        ]);
        Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $primary->id,
            'status' => 'pending',
            'type' => 'one_time',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-1001',
            'status' => 'sent',
            'subtotal' => 240,
            'total' => 240,
            'issued_at' => now()->subDays(5),
            'due_at' => now()->subDay(),
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 100,
            'method' => 'card',
            'paid_at' => now()->subDays(2)->toDateString(),
        ]);

        $page = Livewire::test(ViewCustomer::class, ['record' => $this->customer->id]);

        $snap = $page->instance()->snapshot();
        $this->assertSame(240.0, $snap['lifetimeValue']);
        $this->assertSame(2, $snap['completedCount']);
        $this->assertSame(120.0, $snap['averageJobValue']);
        $this->assertSame(1, $snap['upcomingCount']);
        $this->assertSame(1, $snap['unscheduledCount']);
        $this->assertSame(100.0, $snap['paymentsReceived']);
        // 240 invoiced less the 100 paid.
        $this->assertSame(140.0, $snap['outstanding']);
        $this->assertCount(2, $snap['properties']);

        $page->assertOk()
            ->assertSee('Lifetime revenue')
            ->assertSee('$240.00')
            ->assertSee('Jobs completed')
            // Upcoming work is a byline now, not its own card.
            ->assertSee('1 upcoming')
            ->assertSee('1 awaiting a date')
            ->assertSee('Outstanding')
            ->assertSee('$140.00')
            // Property rows and the invoice both render.
            ->assertSee('88 Cedar Ln')
            ->assertSee('12 Rear Lot')
            ->assertSee('Primary')
            ->assertSee('INV-1001')
            ->assertSee('Overdue')
            // Contact block.
            ->assertSee('marcia@example.com')
            ->assertSee('804-555-0142');
    }

    public function test_the_overview_renders_for_a_customer_with_no_history(): void
    {
        $page = Livewire::test(ViewCustomer::class, ['record' => $this->customer->id]);

        $snap = $page->instance()->snapshot();
        $this->assertSame(0.0, $snap['lifetimeValue']);
        $this->assertSame(0.0, $snap['averageJobValue'], 'no completed jobs must not divide by zero');
        $this->assertNull($snap['nextVisit']);

        $page->assertOk()
            ->assertSee('No properties on file yet.')
            ->assertSee('Nothing upcoming')
            ->assertSee('No completed jobs yet.')
            ->assertSee('Nothing outstanding.');
    }

    public function test_the_upcoming_byline_summarises_scheduled_and_undated_work(): void
    {
        $property = Property::create(['customer_id' => $this->customer->id, 'address' => '5 Pine St']);
        $page = fn () => Livewire::test(ViewCustomer::class, ['record' => $this->customer->id])->instance();

        $this->assertSame('Nothing upcoming', $page()->upcomingByline());

        Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $property->id,
            'status' => 'pending',
            'type' => 'one_time',
        ]);
        $this->assertSame('1 open job, none scheduled', $page()->upcomingByline());

        $next = now()->addDays(2);
        Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $property->id,
            'status' => 'pending',
            'type' => 'one_time',
            'scheduled_date' => $next->toDateString(),
        ]);
        $this->assertSame(
            '1 upcoming · next ' . $next->format('D, M j') . ' · 1 awaiting a date',
            $page()->upcomingByline(),
        );
    }

    public function test_a_paid_invoice_is_left_out_of_the_outstanding_balance(): void
    {
        Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2001',
            'status' => 'paid',
            'subtotal' => 500,
            'total' => 500,
            'issued_at' => now()->subDays(30),
        ]);

        $snap = Livewire::test(ViewCustomer::class, ['record' => $this->customer->id])
            ->instance()
            ->snapshot();

        $this->assertSame(0.0, $snap['outstanding']);
        $this->assertSame(1, $snap['invoiceCount']);
    }
}
