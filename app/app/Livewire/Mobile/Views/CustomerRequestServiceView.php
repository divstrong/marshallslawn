<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Property;
use App\Models\Service;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerRequestServiceView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public ?int $selectedPropertyId = null;
    public ?int $selectedServiceId = null;
    public string $description = '';
    public string $preferredDate = '';
    public bool $submitted = false;

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getCustomerProperty(): ?Customer
    {
        return Customer::find(session('mobile_app_user_id'));
    }

    public function getPropertiesProperty()
    {
        if (!$this->customer) return collect();
        return Property::where('customer_id', $this->customer->id)->get();
    }

    public function getServicesProperty()
    {
        return Service::where('is_active', true)->orderBy('name')->get();
    }

    public function submitRequest()
    {
        if (!$this->customer || !$this->selectedPropertyId || !$this->selectedServiceId) {
            session()->flash('error', 'Please fill in all required fields.');
            return;
        }

        $service = Service::find($this->selectedServiceId);

        Estimate::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->selectedPropertyId,
            'estimate_number' => 'REQ-' . strtoupper(substr(md5(now()), 0, 8)),
            'status' => 'draft',
            'subtotal' => $service?->default_price ?? 0,
            'tax' => 0,
            'total' => $service?->default_price ?? 0,
            'valid_until' => now()->addDays(30),
            'notes' => ($service ? $service->name . ': ' : '') . $this->description,
        ]);

        $this->submitted = true;
        $this->reset('selectedPropertyId', 'selectedServiceId', 'description', 'preferredDate');
    }

    public function resetForm()
    {
        $this->submitted = false;
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-request-service', [
            't' => $this->translations,
        ]);
    }
}
