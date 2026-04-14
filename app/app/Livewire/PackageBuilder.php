<?php

namespace App\Livewire;

use App\Models\Package;
use App\Models\Service;
use Livewire\Component;

class PackageBuilder extends Component
{
    public ?Package $package = null;
    public bool $isNew = true;

    // Package fields
    public string $name = '';
    public string $description = '';
    public string $price = '0.00';
    public bool $isActive = true;

    // Services attached to this package: [['service_id', 'name', 'quantity', 'default_price']]
    public array $packageServices = [];

    // Service search
    public string $serviceSearch = '';
    public bool $showServiceDropdown = false;

    public function mount(?Package $package = null): void
    {
        if ($package && $package->exists) {
            $this->package = $package;
            $this->isNew = false;
            $this->name = $package->name;
            $this->description = $package->description ?? '';
            $this->price = number_format((float) $package->price, 2, '.', '');
            $this->isActive = $package->is_active;

            foreach ($package->services as $service) {
                $this->packageServices[] = [
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'quantity' => $service->pivot->quantity,
                    'default_price' => number_format((float) $service->default_price, 2, '.', ''),
                ];
            }
        }
    }

    // -- Service search --

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) {
            return collect();
        }

        $existingIds = collect($this->packageServices)->pluck('service_id')->toArray();

        return Service::where('is_active', true)
            ->whereNotIn('id', $existingIds)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('full_name', 'like', "%{$this->serviceSearch}%");
            })
            ->limit(8)->get();
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) {
            return;
        }

        // Don't add duplicates
        foreach ($this->packageServices as $ps) {
            if ($ps['service_id'] === $service->id) {
                return;
            }
        }

        $this->packageServices[] = [
            'service_id' => $service->id,
            'name' => $service->name,
            'quantity' => 1,
            'default_price' => number_format((float) $service->default_price, 2, '.', ''),
        ];

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
    }

    public function removeService(int $index): void
    {
        unset($this->packageServices[$index]);
        $this->packageServices = array_values($this->packageServices);
    }

    // -- Computed: sum of individual service prices --

    public function getServicesSubtotalProperty(): string
    {
        $total = 0;
        foreach ($this->packageServices as $ps) {
            $total += (float) $ps['default_price'] * (int) ($ps['quantity'] ?? 1);
        }
        return number_format($total, 2, '.', '');
    }

    public function getSavingsProperty(): string
    {
        $sub = (float) $this->servicesSubtotal;
        $pkg = (float) $this->price;
        if ($sub <= 0 || $pkg >= $sub) {
            return '0.00';
        }
        return number_format($sub - $pkg, 2, '.', '');
    }

    // -- Save --

    public function save(): void
    {
        if (! $this->name) {
            session()->flash('error', 'Package name is required.');
            return;
        }

        if (empty($this->packageServices)) {
            session()->flash('error', 'Please add at least one service.');
            return;
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'price' => (float) $this->price,
            'is_active' => $this->isActive,
        ];

        if ($this->isNew) {
            $this->package = Package::create($data);
            $this->isNew = false;
        } else {
            $this->package->update($data);
        }

        // Sync services
        $syncData = [];
        foreach ($this->packageServices as $ps) {
            $syncData[$ps['service_id']] = ['quantity' => (int) ($ps['quantity'] ?? 1)];
        }
        $this->package->services()->sync($syncData);

        session()->flash('success', 'Package saved successfully.');
    }

    public function render()
    {
        return view('livewire.package-builder');
    }
}
