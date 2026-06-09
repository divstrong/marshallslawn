<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Models\InvoiceCredit;
use Filament\Notifications\Notification;
use Livewire\Component;

/**
 * Custom UI for managing invoice credits on the Edit Invoice page, replacing the
 * Filament repeater. Admins can add/remove credits; the invoice total recalculates
 * on every change.
 */
class InvoiceCreditsManager extends Component
{
    public Invoice $invoice;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public bool $canManage = false;

    public string $newCode = '';
    public string $newDescription = '';
    public ?string $newAmount = null;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->canManage = (bool) auth()->user()?->isAdmin();
        $this->loadLines();
    }

    private function loadLines(): void
    {
        $this->lines = $this->invoice->credits()
            ->with('appliedBy:id,name')
            ->latest()
            ->get()
            ->map(fn (InvoiceCredit $credit) => [
                'id' => (int) $credit->id,
                'code' => $credit->code,
                'description' => $credit->description,
                'amount' => (float) $credit->amount,
                'applied_by' => $credit->appliedBy?->name,
                'created_at' => $credit->created_at?->format('M j, Y g:i A'),
            ])
            ->all();
    }

    public function addCredit(): void
    {
        abort_unless($this->canManage, 403);

        $data = $this->validate([
            'newCode' => ['nullable', 'string', 'max:255'],
            'newDescription' => ['required', 'string', 'max:255'],
            'newAmount' => ['required', 'numeric'],
        ], attributes: [
            'newDescription' => 'description',
            'newAmount' => 'amount',
        ]);

        InvoiceCredit::create([
            'invoice_id' => $this->invoice->id,
            'applied_by' => auth()->id(),
            'code' => $data['newCode'] ?: null,
            'description' => $data['newDescription'],
            'amount' => $data['newAmount'],
        ]);

        $this->reset(['newCode', 'newDescription', 'newAmount']);
        $this->refreshInvoice();

        Notification::make()->title('Credit added')->success()->send();
    }

    public function removeCredit(int $id): void
    {
        abort_unless($this->canManage, 403);

        InvoiceCredit::where('invoice_id', $this->invoice->id)->whereKey($id)->delete();
        $this->refreshInvoice();

        Notification::make()->title('Credit removed')->success()->send();
    }

    private function refreshInvoice(): void
    {
        $this->invoice->recalculateTotal();
        $this->invoice->refresh();
        $this->loadLines();
    }

    public function getCreditsTotalProperty(): float
    {
        return array_sum(array_column($this->lines, 'amount'));
    }

    public function render()
    {
        return view('livewire.invoice-credits-manager');
    }
}
