<?php

namespace App\Livewire;

use App\Mail\ShareEstimateMail;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Property;
use App\Models\Package;
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
    public ?string $squareFootage = null;

    // Estimate fields
    public string $status = 'draft';
    public ?string $validUntil = null;
    public string $notes = '';

    // Line items: array of ['service_id', 'description', 'quantity', 'unit_price', 'total']
    public array $lineItems = [];

    // Totals
    public string $subtotal = '0.00';
    public string $discountTotal = '0.00';
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

    // Package search
    public string $packageSearch = '';
    public bool $showPackageDropdown = false;

    // Discount
    public bool $showDiscountModal = false;
    public string $discountType = 'percent'; // 'percent' or 'dollar'
    public string $discountAmount = '';

    // Lot size (set in customer section, used by pricing calculator)
    public string $selectedLotSize = '';
    public ?string $customLotSqft = null;

    // Pricing calculator modal
    public bool $showPricingCalc = false;
    public array $pricingRows = [];

    public function mount(?Estimate $estimate = null): void
    {
        if ($estimate && $estimate->exists) {
            $this->estimate = $estimate;
            $this->isNew = false;
            $this->customerId = $estimate->customer_id;
            $this->propertyId = $estimate->property_id;
            $this->squareFootage = $estimate->square_footage ? number_format((float) $estimate->square_footage, 2, '.', '') : null;
            $this->status = $estimate->status;
            $this->validUntil = $estimate->valid_until?->format('Y-m-d');
            $this->notes = $estimate->notes ?? '';
            $this->tax = number_format((float) $estimate->tax, 2, '.', '');

            foreach ($estimate->lineItems()->orderBy('sort_order')->get() as $item) {
                $isDiscount = (float) $item->total < 0;
                $this->lineItems[] = [
                    'id' => $item->id,
                    'service_id' => $item->service_id,
                    'description' => $item->description,
                    'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                    'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                    'total' => number_format((float) $item->total, 2, '.', ''),
                    'is_discount' => $isDiscount,
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
            $this->squareFootage = $primary->square_footage ? number_format((float) $primary->square_footage, 2, '.', '') : null;
        }
    }

    public function updatedPropertyId($value): void
    {
        if ($value) {
            $property = Property::find($value);
            $this->squareFootage = $property?->square_footage ? number_format((float) $property->square_footage, 2, '.', '') : null;
        } else {
            $this->squareFootage = null;
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->propertyId = null;
        $this->squareFootage = null;
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
            'is_discount' => false,
        ];

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
        $this->recalculate();
    }

    // -- Package search --

    public function updatedPackageSearch(): void
    {
        $this->showPackageDropdown = strlen($this->packageSearch) >= 1;
    }

    public function getPackageResultsProperty()
    {
        if (strlen($this->packageSearch) < 1) {
            return collect();
        }

        return Package::withCount('services')
            ->where('is_active', true)
            ->where('name', 'like', "%{$this->packageSearch}%")
            ->limit(8)->get();
    }

    public function addPackage(int $packageId): void
    {
        $package = Package::with('services')->find($packageId);
        if (! $package) {
            return;
        }

        $this->lineItems[] = [
            'id' => null,
            'service_id' => null,
            'description' => $package->name,
            'quantity' => '1.00',
            'unit_price' => number_format((float) $package->price, 2, '.', ''),
            'total' => number_format((float) $package->price, 2, '.', ''),
            'is_discount' => false,
        ];

        $this->packageSearch = '';
        $this->showPackageDropdown = false;
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
            'is_discount' => false,
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
        $disc = 0;
        foreach ($this->lineItems as $item) {
            $t = (float) ($item['total'] ?? 0);
            if ($item['is_discount'] ?? false) {
                $disc += $t; // negative value
            } else {
                $sub += $t;
            }
        }
        $this->subtotal = number_format($sub, 2, '.', '');
        $this->discountTotal = number_format($disc, 2, '.', '');
        $tax = (float) ($this->tax ?? 0);
        $this->total = number_format($sub + $disc + $tax, 2, '.', '');
    }

    // -- Discount --

    public function openDiscountModal(): void
    {
        $this->discountType = 'percent';
        $this->discountAmount = '';
        $this->showDiscountModal = true;
    }

    public function closeDiscountModal(): void
    {
        $this->showDiscountModal = false;
    }

    public function applyDiscount(): void
    {
        $amount = (float) $this->discountAmount;
        if ($amount <= 0) {
            return;
        }

        // Calculate the subtotal of non-discount lines
        $sub = 0;
        foreach ($this->lineItems as $item) {
            if (!($item['is_discount'] ?? false)) {
                $sub += (float) ($item['total'] ?? 0);
            }
        }

        if ($this->discountType === 'percent') {
            $discountValue = round($sub * ($amount / 100), 2);
            $label = "Discount ({$amount}%)";
        } else {
            $discountValue = $amount;
            $label = "Discount";
        }

        $this->lineItems[] = [
            'id'          => null,
            'service_id'  => null,
            'description' => $label,
            'quantity'    => '1.00',
            'unit_price'  => number_format(-$discountValue, 2, '.', ''),
            'total'       => number_format(-$discountValue, 2, '.', ''),
            'is_discount' => true,
        ];

        $this->showDiscountModal = false;
        $this->recalculate();
    }

    public function removeDiscount(int $index): void
    {
        $this->removeLine($index);
    }

    public function updatedDiscountType(): void
    {
        // reset amount when switching type
        $this->discountAmount = '';
    }

    // -- Pricing Calculator --

    private function getLotSizeThousands(): ?float
    {
        if ($this->selectedLotSize === '' ) {
            return null;
        }
        if ($this->selectedLotSize === '55+') {
            return $this->customLotSqft ? (float) $this->customLotSqft : null;
        }
        [$from, $to] = explode('-', $this->selectedLotSize);
        return ((float) $from + (float) $to) / 2;
    }

    private function lookupTier(float $sqftThousands): array
    {
        $tiers = config('rate-matrix.tiers');
        $overflow = config('rate-matrix.overflow');

        foreach ($tiers as $tier) {
            if ($sqftThousands >= $tier['from'] && $sqftThousands <= $tier['to']) {
                return $tier;
            }
        }

        if ($sqftThousands > $overflow['above']) {
            $lastTier = end($tiers);
            $excess = $sqftThousands - $overflow['above'];
            $increments = ceil($excess / $overflow['every']);

            return [
                'from'  => $overflow['above'],
                'to'    => $sqftThousands,
                'rate'  => round($lastTier['rate'] + ($increments * $overflow['rate_add']), 2),
                'hours' => round($lastTier['hours'] + ($increments * $overflow['hours_add']), 2),
                'cost'  => round($lastTier['cost'] + ($increments * $overflow['cost_add']), 2),
            ];
        }

        return end($tiers);
    }

    public function openPricingCalc(): void
    {
        $lotSize = $this->getLotSizeThousands();
        $tier = $lotSize ? $this->lookupTier($lotSize) : null;
        $matrixRate = $tier ? $tier['rate'] : null;

        // Build rows from active services, each with rate, qty, visits
        $this->pricingRows = [];
        $services = Service::where('is_active', true)->orderBy('name')->get();

        foreach ($services as $svc) {
            $rate = $matrixRate ?? (float) $svc->default_price;
            $this->pricingRows[] = [
                'service_id'   => $svc->id,
                'name'         => $svc->name,
                'rate'         => number_format($rate, 2, '.', ''),
                'qty'          => '',
                'visits'       => '',
                'total'        => '0.00',
                'use_matrix'   => $matrixRate !== null,
                'default_price' => number_format((float) $svc->default_price, 2, '.', ''),
            ];
        }

        $this->showPricingCalc = true;
    }

    public function closePricingCalc(): void
    {
        $this->showPricingCalc = false;
    }

    public function updatedPricingRows(): void
    {
        foreach ($this->pricingRows as $i => &$row) {
            $qty = (float) ($row['qty'] ?? 0);
            $visits = (float) ($row['visits'] ?? 0);
            $rate = (float) ($row['rate'] ?? 0);
            $row['total'] = number_format($qty * $rate * $visits, 2, '.', '');
        }
        unset($row);
    }

    public function addPricingRowsAsLines(): void
    {
        $added = 0;
        foreach ($this->pricingRows as $row) {
            $total = (float) $row['total'];
            if ($total <= 0) {
                continue;
            }

            $qty = (float) ($row['qty'] ?? 1);
            $visits = (float) ($row['visits'] ?? 1);
            $rate = (float) $row['rate'];
            // unit_price = rate × visits (so qty × unit_price = total)
            $unitPrice = $rate * $visits;

            $this->lineItems[] = [
                'id'          => null,
                'service_id'  => $row['service_id'],
                'description' => $row['name'] . ' (' . (int) $visits . ' visits)',
                'quantity'    => number_format($qty, 2, '.', ''),
                'unit_price'  => number_format($unitPrice, 2, '.', ''),
                'total'       => number_format($total, 2, '.', ''),
                'is_discount' => false,
            ];
            $added++;
        }

        $this->showPricingCalc = false;
        $this->recalculate();
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
            'square_footage' => $this->squareFootage ? (float) $this->squareFootage : null,
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
