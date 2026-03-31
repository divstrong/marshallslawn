<?php

namespace App\Livewire;

use App\Mail\ShareEstimateMail;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Property;
use App\Models\Service;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class EstimateBuilder extends Component
{
    public ?Estimate $estimate = null;
    public bool $isNew = true;

    // Customer
    public ?int $customerId = null;
    public string $customerSearch = '';
    public bool $showCustomerDropdown = false;

    // Quick-create customer modal
    public bool $showNewCustomerModal = false;
    public string $newCustFirstName = '';
    public string $newCustLastName = '';
    public string $newCustEmail = '';
    public string $newCustPhone = '';
    public string $newCustCompany = '';

    // Property
    public ?int $propertyId = null;

    // Estimate fields
    public string $status = 'draft';
    public ?string $validUntil = null;
    public string $notes = '';

    // Line items: array of ['service_id', 'description', 'quantity', 'unit_price', 'total']
    public array $lineItems = [];

    // Totals
    public string $subtotal = '0.00';
    public string $tax = '0.00';
    public string $total = '0.00';
    public float $taxRate = 0;

    // Share modal
    public bool $showShareModal = false;
    public string $shareEmail = '';
    public string $shareMessage = '';
    public bool $shareSent = false;

    // Service search for adding lines
    public string $serviceSearch = '';
    public bool $showServiceDropdown = false;

    public function mount(?Estimate $estimate = null): void
    {
        if ($estimate && $estimate->exists) {
            $this->estimate = $estimate;
            $this->isNew = false;
            $this->customerId = $estimate->customer_id;
            $this->propertyId = $estimate->property_id;
            $this->status = $estimate->status;
            $this->validUntil = $estimate->valid_until?->format('Y-m-d');
            $this->notes = $estimate->notes ?? '';
            $this->tax = number_format((float) $estimate->tax, 2, '.', '');

            foreach ($estimate->lineItems()->orderBy('sort_order')->get() as $item) {
                $this->lineItems[] = [
                    'id' => $item->id,
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                    'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                    'total' => number_format((float) $item->total, 2, '.', ''),
                ];
            }

            $this->recalculate();

            // Pre-fill share email
            if ($estimate->customer?->email) {
                $this->shareEmail = $estimate->customer->email;
            }
        } else {
            $this->validUntil = now()->addDays(30)->format('Y-m-d');
        }
    }

    // -- Customer search --

    public function updatedCustomerSearch(): void
    {
        $this->showCustomerDropdown = strlen($this->customerSearch) >= 1;
    }

    public function getCustomerResultsProperty()
    {
        if (strlen($this->customerSearch) < 1) return collect();

        return Customer::where(function ($q) {
            $q->where('first_name', 'like', "%{$this->customerSearch}%")
              ->orWhere('last_name', 'like', "%{$this->customerSearch}%")
              ->orWhere('company_name', 'like', "%{$this->customerSearch}%");
        })->limit(8)->get();
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = '';
        $this->showCustomerDropdown = false;
        $this->propertyId = null;

        $customer = Customer::find($id);
        if ($customer?->email) {
            $this->shareEmail = $customer->email;
        }

        // Auto-select primary property
        $primary = Property::where('customer_id', $id)->where('is_primary', true)->first();
        if ($primary) {
            $this->propertyId = $primary->id;
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->propertyId = null;
        $this->shareEmail = '';
    }

    public function openNewCustomerModal(): void
    {
        $this->newCustFirstName = '';
        $this->newCustLastName = '';
        $this->newCustEmail = '';
        $this->newCustPhone = '';
        $this->newCustCompany = '';
        $this->showNewCustomerModal = true;
    }

    public function closeNewCustomerModal(): void
    {
        $this->showNewCustomerModal = false;
    }

    public function createAndSelectCustomer(): void
    {
        if (! $this->newCustFirstName || ! $this->newCustLastName) {
            return;
        }

        $customer = Customer::create([
            'first_name' => $this->newCustFirstName,
            'last_name' => $this->newCustLastName,
            'email' => $this->newCustEmail ?: null,
            'phone' => $this->newCustPhone ?: null,
            'company_name' => $this->newCustCompany ?: null,
            'status' => 'active',
        ]);

        $this->selectCustomer($customer->id);
        $this->showNewCustomerModal = false;
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    public function getCustomerPropertiesProperty()
    {
        if (! $this->customerId) return collect();
        return Property::where('customer_id', $this->customerId)->get();
    }

    // -- Service search / line items --

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) return collect();

        return Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('full_name', 'like', "%{$this->serviceSearch}%");
            })
            ->limit(8)->get();
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) return;

        $price = (float) ($service->default_price ?? 0);
        $this->lineItems[] = [
            'id' => null,
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => '1.00',
            'unit_price' => number_format($price, 2, '.', ''),
            'total' => number_format($price, 2, '.', ''),
        ];

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
        $this->recalculate();
    }

    public function addCustomLine(): void
    {
        $this->lineItems[] = [
            'id' => null,
            'service_id' => null,
            'description' => '',
            'quantity' => '1.00',
            'unit_price' => '0.00',
            'total' => '0.00',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
        $this->recalculate();
    }

    public function updatedLineItems(): void
    {
        $this->recalculateLineTotals();
    }

    public function updatedTax(): void
    {
        $this->recalculate();
    }

    private function recalculateLineTotals(): void
    {
        foreach ($this->lineItems as $i => &$item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['total'] = number_format($qty * $price, 2, '.', '');
        }
        unset($item);
        $this->recalculate();
    }

    private function recalculate(): void
    {
        $sub = 0;
        foreach ($this->lineItems as $item) {
            $sub += (float) ($item['total'] ?? 0);
        }
        $this->subtotal = number_format($sub, 2, '.', '');
        $tax = (float) ($this->tax ?? 0);
        $this->total = number_format($sub + $tax, 2, '.', '');
    }

    // -- Save --

    public function save(): void
    {
        if (! $this->customerId) {
            session()->flash('error', 'Please select a customer.');
            return;
        }

        $data = [
            'customer_id' => $this->customerId,
            'property_id' => $this->propertyId,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'valid_until' => $this->validUntil ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->isNew) {
            $this->estimate = Estimate::create($data);
            $this->isNew = false;
        } else {
            $this->estimate->update($data);
        }

        // Sync line items
        $existingIds = [];
        foreach ($this->lineItems as $i => $item) {
            $lineData = [
                'estimate_id' => $this->estimate->id,
                'service_id' => $item['service_id'] ?: null,
                'description' => $item['description'] ?: 'Service',
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'total' => (float) $item['total'],
                'sort_order' => $i,
            ];

            if (! empty($item['id'])) {
                EstimateLineItem::where('id', $item['id'])->update($lineData);
                $existingIds[] = $item['id'];
            } else {
                $line = EstimateLineItem::create($lineData);
                $this->lineItems[$i]['id'] = $line->id;
                $existingIds[] = $line->id;
            }
        }

        // Remove deleted lines
        EstimateLineItem::where('estimate_id', $this->estimate->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        $this->estimate->refresh();

        session()->flash('success', 'Estimate saved successfully.');
    }

    // -- Share --

    public function openShareModal(): void
    {
        if ($this->isNew) {
            $this->save();
        }

        $customer = $this->selectedCustomer;
        $this->shareEmail = $customer?->email ?? '';
        $this->shareMessage = "Hi {$customer?->first_name},\n\nPlease find your estimate from Marshall's Lawn & Landscape attached. Click the link below to view the details.\n\nThank you for your business!";
        $this->shareSent = false;
        $this->showShareModal = true;
    }

    public function closeShareModal(): void
    {
        $this->showShareModal = false;
    }

    public function sendEstimate(): void
    {
        if (! $this->shareEmail || ! $this->estimate) return;

        Mail::to($this->shareEmail)->send(
            new ShareEstimateMail($this->estimate, $this->shareMessage)
        );

        $this->estimate->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $this->status = 'sent';

        $this->shareSent = true;
    }

    public function render()
    {
        return view('livewire.estimate-builder');
    }
}
