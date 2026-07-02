<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerMessage;
use Livewire\Component;

/**
 * The office side of the customer <-> office conversation. Rendered inside a
 * chat modal opened from the Customer resource or a job's customer.
 */
class CustomerChatPanel extends Component
{
    public int $customerId;

    public string $body = '';

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;

        // Opening the panel marks the customer's unread messages as read.
        CustomerMessage::query()
            ->where('customer_id', $this->customerId)
            ->where('sender', CustomerMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getCustomerProperty(): ?Customer
    {
        return Customer::find($this->customerId);
    }

    public function getMessagesProperty()
    {
        return CustomerMessage::query()
            ->with('senderUser:id,name')
            ->where('customer_id', $this->customerId)
            ->orderBy('created_at')
            ->limit(300)
            ->get();
    }

    public function send(): void
    {
        $body = trim($this->body);
        if ($body === '') {
            return;
        }

        CustomerMessage::create([
            'customer_id' => $this->customerId,
            'sender' => CustomerMessage::SENDER_OFFICE,
            'sender_user_id' => auth()->id(),
            'body' => $body,
        ]);

        $this->body = '';
    }

    public function render()
    {
        return view('livewire.customer-chat-panel');
    }
}
