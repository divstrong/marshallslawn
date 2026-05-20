<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Estimate;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerEstimatesView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $filter = 'all';
    public ?int $viewingEstimateId = null;

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');
        return $customerId ? Customer::find($customerId) : null;
    }

    public function getEstimatesProperty()
    {
        if (!$this->customer) return collect();

        $query = Estimate::where('customer_id', $this->customer->id)
            ->with(['property', 'lineItems'])
            ->orderByDesc('created_at');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query->get();
    }

    public function getViewingEstimateProperty(): ?Estimate
    {
        if (!$this->viewingEstimateId) return null;
        return Estimate::with(['property', 'lineItems', 'lineItems.service'])->find($this->viewingEstimateId);
    }

    public function viewEstimate(int $id)
    {
        $this->viewingEstimateId = $id;
    }

    public function closeEstimate()
    {
        $this->viewingEstimateId = null;
    }

    public function approveEstimate(int $id)
    {
        $estimate = Estimate::where('customer_id', $this->customer?->id)->find($id);
        if ($estimate) {
            $estimate->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
            session()->flash('success', 'Estimate approved!');
        }
    }

    public function declineEstimate(int $id)
    {
        $estimate = Estimate::where('customer_id', $this->customer?->id)->find($id);
        if ($estimate) {
            $estimate->update(['status' => 'declined']);
            session()->flash('success', 'Estimate declined.');
        }
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-estimates', [
            't' => $this->translations,
        ]);
    }
}
