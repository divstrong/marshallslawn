<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\InvoiceCredit;
use App\Models\InvoiceLineItem;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Builds an invoice from priced service lines rather than typed totals.
 *
 * Subtotal and total are never entered — they are derived from the lines, the
 * discounts, the tax and the credits, and recomputed on every keystroke so the
 * preview beside the form always matches what will be saved. Filament's form
 * components can't express the row grid plus live preview, hence a Livewire
 * component (matching how EstimateBuilder handles the same job for estimates).
 */
class InvoiceBuilder extends Component
{
    /** An existing invoice being edited, or null while creating. */
    public ?Invoice $invoice = null;

    public bool $isNew = true;

    // -- Header --
    public ?int $customerId = null;

    public string $customerSearch = '';

    public bool $showCustomerDropdown = false;

    public ?int $estimateId = null;

    public string $estimateSearch = '';

    public bool $showEstimateDropdown = false;

    public string $status = 'draft';

    public ?string $issuedAt = null;

    public ?string $dueAt = null;

    public bool $allowsPaymentPlan = true;

    public string $notes = '';

    public string $tax = '0.00';

    // -- Rows --
    /** @var array<int, array{service_id: ?int, description: string, quantity: string, unit_price: string}> */
    public array $lines = [];

    /** @var array<int, array{description: string, amount: string}> */
    public array $discounts = [];

    /** @var array<int, array{code: string, description: string, amount: string}> */
    public array $credits = [];

    public string $serviceSearch = '';

    public bool $showServiceDropdown = false;

    public function mount(?Invoice $invoice = null, ?int $customerId = null): void
    {
        if ($invoice && $invoice->exists) {
            $this->invoice = $invoice;
            $this->isNew = false;
            $this->hydrateFromInvoice($invoice);

            return;
        }

        $this->issuedAt = now()->toDateString();
        $this->dueAt = now()->addDays(30)->toDateString();

        if ($customerId && Customer::whereKey($customerId)->exists()) {
            $this->selectCustomer($customerId);
        }
    }

    private function hydrateFromInvoice(Invoice $invoice): void
    {
        $this->customerId = $invoice->customer_id;
        $this->estimateId = $invoice->estimate_id;
        $this->status = $invoice->status ?: 'draft';
        $this->issuedAt = $invoice->issued_at?->toDateString();
        $this->dueAt = $invoice->due_at?->toDateString();
        $this->allowsPaymentPlan = (bool) $invoice->allows_payment_plan;
        $this->notes = $invoice->notes ?? '';
        $this->tax = $this->money($invoice->tax);

        foreach ($invoice->lineItems()->orderBy('sort_order')->get() as $item) {
            // Negative lines are how a discount is stored, so they come back as one.
            if ((float) $item->total < 0) {
                $this->discounts[] = [
                    'description' => $item->description ?: 'Discount',
                    'amount' => $this->money(abs((float) $item->total)),
                ];

                continue;
            }

            $this->lines[] = [
                'service_id' => $item->service_id ? (int) $item->service_id : null,
                'description' => $item->description ?? '',
                'quantity' => $this->money($item->quantity),
                'unit_price' => $this->money($item->unit_price),
            ];
        }

        foreach ($invoice->credits as $credit) {
            $this->credits[] = [
                'code' => $credit->code ?? '',
                'description' => $credit->description ?? '',
                'amount' => $this->money($credit->amount),
            ];
        }
    }

    // ---------------------------------------------------------------- customer

    public function updatedCustomerSearch(): void
    {
        $this->showCustomerDropdown = strlen($this->customerSearch) >= 1;
    }

    public function getCustomerResultsProperty()
    {
        if (strlen($this->customerSearch) < 1) {
            return collect();
        }

        return Customer::where(function ($q) {
            $q->where('first_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('last_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('company_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('email', 'like', "%{$this->customerSearch}%");
        })->orderBy('last_name')->limit(8)->get();
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = '';
        $this->showCustomerDropdown = false;
        // An estimate belongs to one customer, so a switch invalidates the link.
        $this->clearEstimate();
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->clearEstimate();
    }

    public function getCustomerProperty(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    // ---------------------------------------------------------------- estimate

    public function updatedEstimateSearch(): void
    {
        $this->showEstimateDropdown = true;
    }

    /**
     * The chosen customer's estimates, newest first, narrowed by the search box.
     * Searched rather than listed in full: a long-standing customer accumulates
     * more estimates than a dropdown can usefully hold.
     */
    public function getEstimateResultsProperty()
    {
        if (! $this->customerId) {
            return collect();
        }

        $search = trim($this->estimateSearch);

        return Estimate::where('customer_id', $this->customerId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('estimate_number', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'estimate_number', 'status', 'total', 'created_at']);
    }

    public function selectEstimate(int $id): void
    {
        // Guard the relationship rather than trusting the click: an estimate from
        // another customer must never end up attached to this invoice.
        $estimate = Estimate::where('id', $id)
            ->where('customer_id', $this->customerId)
            ->first();

        if (! $estimate) {
            return;
        }

        $this->estimateId = (int) $estimate->id;
        $this->estimateSearch = '';
        $this->showEstimateDropdown = false;
    }

    public function clearEstimate(): void
    {
        $this->estimateId = null;
        $this->estimateSearch = '';
        $this->showEstimateDropdown = false;
    }

    public function getSelectedEstimateProperty(): ?Estimate
    {
        return $this->estimateId ? Estimate::find($this->estimateId) : null;
    }

    /**
     * Pull the linked estimate's line items in as invoice lines. Additive, so an
     * accidental double-click doesn't silently wipe hand-entered work — the office
     * can remove rows if they didn't mean it.
     */
    public function importFromEstimate(): void
    {
        if (! $this->estimateId) {
            return;
        }

        $estimate = Estimate::with('lineItems')->find($this->estimateId);
        if (! $estimate || $estimate->customer_id !== $this->customerId) {
            return;
        }

        foreach ($estimate->lineItems()->orderBy('sort_order')->get() as $item) {
            if ((float) $item->total < 0) {
                $this->discounts[] = [
                    'description' => $item->description ?: 'Discount',
                    'amount' => $this->money(abs((float) $item->total)),
                ];

                continue;
            }

            $this->lines[] = [
                'service_id' => $item->service_id ? (int) $item->service_id : null,
                'description' => $item->description ?? '',
                'quantity' => $this->money($item->quantity),
                'unit_price' => $this->money($item->unit_price),
            ];
        }

        if (filled($estimate->tax) && (float) $estimate->tax > 0) {
            $this->tax = $this->money($estimate->tax);
        }
    }

    // ------------------------------------------------------------------- lines

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) {
            return collect();
        }

        return Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                    ->orWhere('full_name', 'like', "%{$this->serviceSearch}%")
                    ->orWhere('code', 'like', "%{$this->serviceSearch}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'default_price']);
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) {
            return;
        }

        $this->lines[] = [
            'service_id' => (int) $service->id,
            'description' => $service->name,
            'quantity' => '1.00',
            // A service with no default rate starts at zero for the office to price.
            'unit_price' => $this->money($service->default_price ?? 0),
        ];

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
    }

    public function addCustomLine(): void
    {
        $this->lines[] = [
            'service_id' => null,
            'description' => '',
            'quantity' => '1.00',
            'unit_price' => '0.00',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function addDiscount(): void
    {
        $this->discounts[] = ['description' => 'Discount', 'amount' => '0.00'];
    }

    public function removeDiscount(int $index): void
    {
        unset($this->discounts[$index]);
        $this->discounts = array_values($this->discounts);
    }

    public function addCredit(): void
    {
        $this->credits[] = ['code' => '', 'description' => '', 'amount' => '0.00'];
    }

    public function removeCredit(int $index): void
    {
        unset($this->credits[$index]);
        $this->credits = array_values($this->credits);
    }

    // ------------------------------------------------------------------ totals

    public function lineTotal(int $index): float
    {
        $row = $this->lines[$index] ?? null;
        if (! $row) {
            return 0.0;
        }

        return round((float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0), 2);
    }

    public function getServicesTotalProperty(): float
    {
        $sum = 0.0;
        foreach (array_keys($this->lines) as $index) {
            $sum += $this->lineTotal($index);
        }

        return round($sum, 2);
    }

    public function getDiscountTotalProperty(): float
    {
        return round(array_sum(array_map(
            fn (array $row): float => abs((float) ($row['amount'] ?? 0)),
            $this->discounts,
        )), 2);
    }

    /** What the invoice's `subtotal` column will hold: services less discounts. */
    public function getSubtotalProperty(): float
    {
        return round($this->servicesTotal - $this->discountTotal, 2);
    }

    public function getTaxAmountProperty(): float
    {
        return round((float) $this->tax, 2);
    }

    public function getCreditsTotalProperty(): float
    {
        return round(array_sum(array_map(
            fn (array $row): float => abs((float) ($row['amount'] ?? 0)),
            $this->credits,
        )), 2);
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->subtotal + $this->taxAmount - $this->creditsTotal, 2);
    }

    // -------------------------------------------------------------------- save

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'customerId' => ['required', 'integer', 'exists:customers,id'],
            'estimateId' => ['nullable', 'integer', 'exists:estimates,id'],
            'status' => ['required', 'in:draft,sent,paid,overdue,payment_plan,cancelled'],
            'issuedAt' => ['nullable', 'date'],
            'dueAt' => ['nullable', 'date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'lines' => ['array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discounts.*.description' => ['required', 'string', 'max:255'],
            'discounts.*.amount' => ['required', 'numeric', 'min:0'],
            'credits.*.description' => ['required', 'string', 'max:255'],
            'credits.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'customerId.required' => 'Choose the customer this invoice is for.',
            'lines.min' => 'Add at least one service — the invoice total comes from its lines.',
            'lines.*.description.required' => 'Every line needs a description.',
            'discounts.*.description.required' => 'Give each discount a description.',
            'credits.*.description.required' => 'Give each credit a description.',
        ];
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->validationMessages());

        // The total can't be negative: a discount or credit bigger than the work
        // would mean we owe the customer, which is a refund, not an invoice.
        if ($this->grandTotal < 0) {
            $this->addError('discounts', 'Discounts and credits exceed the work billed — the total cannot be negative.');

            return;
        }

        $invoice = DB::transaction(function (): Invoice {
            $attributes = [
                'customer_id' => $this->customerId,
                'estimate_id' => $this->estimateId,
                'status' => $this->status,
                'issued_at' => $this->issuedAt ?: null,
                'due_at' => $this->dueAt ?: null,
                'allows_payment_plan' => $this->allowsPaymentPlan,
                'notes' => $this->notes ?: null,
                'tax' => $this->taxAmount,
                // Derived, never typed.
                'subtotal' => $this->subtotal,
                'credits_total' => $this->creditsTotal,
                'total' => $this->grandTotal,
            ];

            if ($this->invoice && $this->invoice->exists) {
                $invoice = $this->invoice;
                $invoice->update($attributes);
                $invoice->lineItems()->delete();
                $invoice->credits()->delete();
            } else {
                $invoice = Invoice::create($attributes);
            }

            $sort = 0;
            foreach (array_keys($this->lines) as $index) {
                $row = $this->lines[$index];
                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $row['service_id'] ?: null,
                    'description' => $row['description'],
                    'quantity' => (float) $row['quantity'],
                    'unit_price' => (float) $row['unit_price'],
                    'total' => $this->lineTotal($index),
                    'sort_order' => $sort++,
                ]);
            }

            // Discounts ride along as negative lines so the subtotal a customer sees
            // on the invoice matches the sum of everything printed on it.
            foreach ($this->discounts as $row) {
                $amount = abs((float) $row['amount']);
                if ($amount <= 0) {
                    continue;
                }

                InvoiceLineItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => null,
                    'description' => $row['description'],
                    'quantity' => 1,
                    'unit_price' => -$amount,
                    'total' => -$amount,
                    'sort_order' => $sort++,
                ]);
            }

            foreach ($this->credits as $row) {
                $amount = abs((float) $row['amount']);
                if ($amount <= 0) {
                    continue;
                }

                InvoiceCredit::create([
                    'invoice_id' => $invoice->id,
                    'applied_by' => Auth::id(),
                    'code' => $row['code'] ?: null,
                    'description' => $row['description'],
                    'amount' => $amount,
                ]);
            }

            // Authoritative recompute from what actually landed in the database.
            $invoice->recalculateFromLineItems();

            return $invoice->fresh();
        });

        session()->flash('invoice-saved', "Invoice {$invoice->invoice_number} saved.");

        $this->redirect(
            \App\Filament\Resources\InvoiceResource::getUrl('edit', ['record' => $invoice->id]),
            navigate: false,
        );
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public function render()
    {
        return view('livewire.invoice-builder');
    }
}
