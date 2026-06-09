<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Custom UI for recording invoice payments on the Edit Invoice page, replacing
 * the Filament repeater. Admins record/remove payments; the invoice is marked
 * paid automatically once the balance is covered.
 */
class InvoicePaymentsManager extends Component
{
    public Invoice $invoice;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public bool $canManage = false;

    public ?string $newAmount = null;
    public string $newMethod = 'card';
    public ?string $newPaidAt = null;
    public string $newReference = '';
    public string $newNotes = '';

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->canManage = (bool) auth()->user()?->isAdmin();
        $this->newPaidAt = now()->toDateString();
        $this->loadLines();
    }

    private function loadLines(): void
    {
        $this->lines = $this->invoice->payments()
            ->with('recordedBy:id,name')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => (int) $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'method_label' => Payment::METHODS[$payment->method] ?? $payment->method,
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->format('M j, Y'),
                'recorded_by' => $payment->recordedBy?->name,
                'notes' => $payment->notes,
            ])
            ->all();
    }

    public function addPayment(): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'newAmount' => ['required', 'numeric', 'gt:0'],
            'newMethod' => ['required', 'in:' . implode(',', array_keys(Payment::METHODS))],
            'newPaidAt' => ['required', 'date'],
            'newReference' => ['nullable', 'string', 'max:255'],
            'newNotes' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'newAmount' => 'amount',
            'newPaidAt' => 'paid date',
        ]);

        Payment::create([
            'invoice_id' => $this->invoice->id,
            'amount' => $data['newAmount'],
            'method' => $data['newMethod'],
            'paid_at' => $data['newPaidAt'],
            'reference' => $data['newReference'] ?: null,
            'notes' => $data['newNotes'] ?: null,
        ]);

        $this->reset(['newAmount', 'newReference', 'newNotes']);
        $this->newMethod = 'card';
        $this->newPaidAt = now()->toDateString();

        $this->afterChange();

        Notification::make()->title('Payment recorded')->success()->send();
    }

    public function removePayment(int $id): void
    {
        abort_unless($this->canManage, 403);

        Payment::where('invoice_id', $this->invoice->id)->whereKey($id)->delete();
        $this->afterChange();

        Notification::make()->title('Payment removed')->success()->send();
    }

    private function afterChange(): void
    {
        $this->invoice->refresh();

        // Mark the invoice paid once payments cover the balance.
        if ($this->invoice->amountPaid() > 0
            && $this->invoice->balanceDue() <= 0
            && in_array($this->invoice->status, ['draft', 'sent', 'overdue', 'payment_plan'], true)) {
            $this->invoice->update([
                'status' => 'paid',
                'paid_at' => $this->invoice->paid_at ?? now()->toDateString(),
            ]);
        }

        $this->loadLines();
    }

    public function getTotalProperty(): float
    {
        return (float) $this->invoice->total;
    }

    public function getPaidProperty(): float
    {
        return $this->invoice->amountPaid();
    }

    public function getBalanceProperty(): float
    {
        return $this->invoice->balanceDue();
    }

    public function render()
    {
        return view('livewire.invoice-payments-manager');
    }
}
