<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\CustomerMessage;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerChatView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $body = '';

    public function mount(): void
    {
        $this->language = session('mobile_app_language', 'en');

        // Mark office messages as read when the customer opens the chat.
        if ($id = session('mobile_app_user_id')) {
            CustomerMessage::query()
                ->where('customer_id', $id)
                ->where('sender', CustomerMessage::SENDER_OFFICE)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');

        return $customerId ? Customer::find($customerId) : null;
    }

    public function getMessagesProperty()
    {
        if (! $this->customer) {
            return collect();
        }

        return CustomerMessage::where('customer_id', $this->customer->id)
            ->orderBy('created_at')
            ->limit(300)
            ->get();
    }

    public function send(): void
    {
        $body = trim($this->body);
        if ($body === '' || ! $this->customer) {
            return;
        }

        CustomerMessage::create([
            'customer_id' => $this->customer->id,
            'sender' => CustomerMessage::SENDER_CUSTOMER,
            'body' => $body,
        ]);

        $this->body = '';
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-chat', [
            't' => $this->translations,
        ]);
    }
}
