<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Payment;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerPaymentsView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public function mount(): void
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');

        return $customerId ? Customer::find($customerId) : null;
    }

    public function getPaymentsProperty()
    {
        if (! $this->customer) {
            return collect();
        }

        return Payment::where('customer_id', $this->customer->id)
            ->with('invoice:id,invoice_number')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getTotalPaidProperty(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-payments', [
            't' => $this->translations,
            'methodLabels' => Payment::METHODS,
        ]);
    }
}
