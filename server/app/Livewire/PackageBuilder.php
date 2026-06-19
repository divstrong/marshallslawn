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

    // Sub-packages bundled into this package: [['package_id', 'name', 'quantity', 'price']]
    public array $packageSubPackages = [];

    // Service search
    public string $serviceSearch = '';
    public bool $showServiceDropdown = false;

    // Sub-package search
    public string $packageSearch = '';
    public bool $showPackageDropdown = false;

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

            foreach ($package->subPackages as $sub) {
                $this->packageSubPackages[] = [
                    'package_id' => $sub->id,
                    'name' => $sub->name,
                    'quantity' => $sub->pivot->quantity,
                    'price' => number_format((float) $sub->price, 2, '.', ''),
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

    // -- Sub-package search (issue #20) --

    public function updatedPackageSearch(): void
    {
        $this->showPackageDropdown = strlen($this->packageSearch) >= 1;
    }

    public function getPackageResultsProperty()
    {
        if (strlen($this->packageSearch) < 1) {
            return collect();
        }

        // Exclude self, anything already added, and any package that would form a
        // cycle (a package that already contains this one, directly or transitively).
        $blocked = collect($this->packageSubPackages)->pluck('package_id')->all();
        if ($this->package) {
            $blocked = array_merge($blocked, $this->package->disallowedSubPackageIds());
        }

        return Package::where('is_active', true)
            ->whereNotIn('id', $blocked)
            ->where('name', 'like', "%{$this->packageSearch}%")
            ->limit(8)->get();
    }

    public function addSubPackage(int $packageId): void
    {
        $package = Package::find($packageId);
        if (! $package) {
            return;
        }

        // Never let a package contain itself.
        if ($this->package && $package->id === $this->package->id) {
            return;
        }

        foreach ($this->packageSubPackages as $sp) {
            if ($sp['package_id'] === $package->id) {
                return;
            }
        }

        $this->packageSubPackages[] = [
            'package_id' => $package->id,
            'name' => $package->name,
            'quantity' => 1,
            'price' => number_format((float) $package->price, 2, '.', ''),
        ];

        $this->packageSearch = '';
        $this->showPackageDropdown = false;
    }

    public function removeSubPackage(int $index): void
    {
        unset($this->packageSubPackages[$index]);
        $this->packageSubPackages = array_values($this->packageSubPackages);
    }

    // -- Computed: sums --

    public function getServicesSubtotalProperty(): string
    {
        $total = 0;
        foreach ($this->packageServices as $ps) {
            $total += (float) $ps['default_price'] * (int) ($ps['quantity'] ?? 1);
        }
        foreach ($this->packageSubPackages as $sp) {
            $total += (float) $sp['price'] * (int) ($sp['quantity'] ?? 1);
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

        if (empty($this->packageServices) && empty($this->packageSubPackages)) {
            session()->flash('error', 'Please add at least one service or package.');
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

        // Sync sub-packages, guarding against self-reference (issue #20).
        $subSync = [];
        foreach ($this->packageSubPackages as $sp) {
            if ((int) $sp['package_id'] === (int) $this->package->id) {
                continue;
            }
            $subSync[$sp['package_id']] = ['quantity' => (int) ($sp['quantity'] ?? 1)];
        }
        $this->package->subPackages()->sync($subSync);

        session()->flash('success', 'Package saved successfully.');
    }

    public function render()
    {
        return view('livewire.package-builder');
    }
}
