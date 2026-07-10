<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerMessage;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The office side of the customer <-> office conversation. Rendered inside a
 * slide-over opened from the Customer resource or a job's customer. Mirrors the
 * conversation pane of the employee chat center so the two feel identical
 * (issue #48).
 */
class CustomerChatPanel extends Component
{
    public int $customerId;

    public string $body = '';

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->markRead();
    }

    private function markRead(): void
    {
        // Opening the panel marks the customer's unread messages as read.
        CustomerMessage::query()
            ->where('customer_id', $this->customerId)
            ->where('sender', CustomerMessage::SENDER_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Header details for the conversation, shaped like the employee chat's
     * selectedEmployee so the two views share one template shape.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function header(): ?array
    {
        $customer = Customer::find($this->customerId);
        if (! $customer) {
            return null;
        }

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            ?: ($customer->company_name ?: 'Customer');

        return [
            'name' => $name,
            'subtitle' => $customer->company_name && $name !== $customer->company_name
                ? $customer->company_name
                : ($customer->phone ?: null),
            'initials' => $this->initials($customer),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function messages(): array
    {
        return CustomerMessage::query()
            ->with('senderUser:id,name')
            ->where('customer_id', $this->customerId)
            ->orderBy('created_at')
            ->limit(500)
            ->get()
            ->map(fn (CustomerMessage $message) => [
                'id' => (int) $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender === CustomerMessage::SENDER_OFFICE
                    ? ($message->senderUser?->name ?? 'Office')
                    : 'Customer',
                'body' => $message->body,
                'time' => $message->created_at?->format('M j, g:i A'),
            ])
            ->all();
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
        unset($this->messages);
        $this->dispatch('customer-chat:updated');
    }

    private function initials(Customer $customer): string
    {
        $initials = strtoupper(
            substr($customer->first_name ?? '', 0, 1) . substr($customer->last_name ?? '', 0, 1)
        );

        if ($initials === '') {
            $initials = strtoupper(substr($customer->company_name ?? '?', 0, 2));
        }

        return $initials ?: '?';
    }

    public function render()
    {
        return view('livewire.customer-chat-panel');
    }
}
