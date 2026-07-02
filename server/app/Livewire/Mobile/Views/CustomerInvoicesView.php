<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Invoice;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerInvoicesView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public ?int $viewingInvoiceId = null;

    public function mount(): void
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');

        return $customerId ? Customer::find($customerId) : null;
    }

    public function getInvoicesProperty()
    {
        if (! $this->customer) {
            return collect();
        }

        return Invoice::where('customer_id', $this->customer->id)
            ->withCount('payments')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getViewingInvoiceProperty(): ?Invoice
    {
        if (! $this->viewingInvoiceId) {
            return null;
        }

        return Invoice::where('customer_id', $this->customer?->id)
            ->with('lineItems', 'payments')
            ->find($this->viewingInvoiceId);
    }

    public function viewInvoice(int $id): void
    {
        $this->viewingInvoiceId = $id;
    }

    public function close(): void
    {
        $this->viewingInvoiceId = null;
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-invoices', [
            't' => $this->translations,
        ]);
    }
}
