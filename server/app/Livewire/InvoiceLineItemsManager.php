<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Service;
use Livewire\Component;

class InvoiceLineItemsManager extends Component
{
    public Invoice $invoice;

    public string $serviceSearch = '';
    public bool $showServiceDropdown = false;

    /** Working copy: ['id', 'service_id', 'description', 'quantity', 'unit_price', 'total']. */
    public array $lines = [];

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->loadLines();
    }

    private function loadLines(): void
    {
        $this->lines = $this->invoice->lineItems()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (InvoiceLineItem $line) => [
                'id' => (int) $line->id,
                'service_id' => $line->service_id ? (int) $line->service_id : null,
                'description' => $line->description ?? '',
                'quantity' => number_format((float) $line->quantity, 2, '.', ''),
                'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
                'total' => number_format((float) $line->total, 2, '.', ''),
            ])
            ->all();
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) {
            return collect();
        }

        return Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('full_name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('code', 'like', "%{$this->serviceSearch}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'default_price']);
    }

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) {
            return;
        }

        $price = (float) ($service->default_price ?? 0);
        InvoiceLineItem::create([
            'invoice_id' => $this->invoice->id,
            'service_id' => $service->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => $price,
            'total' => $price,
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
        $this->loadLines();
        $this->refreshInvoice();
    }

    public function addCustomLine(): void
    {
        InvoiceLineItem::create([
            'invoice_id' => $this->invoice->id,
            'service_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->loadLines();
    }

    /**
     * Persist edits to a row. Quantity/price changes recompute that row's total
     * and the invoice subtotal/total.
     */
    public function updatedLines($value, $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $row = $this->lines[(int) $index] ?? null;
        if (! $row || empty($row['id'])) {
            return;
        }

        $qty = (float) ($row['quantity'] ?? 0);
        $price = (float) ($row['unit_price'] ?? 0);
        $total = round($qty * $price, 2);
        $this->lines[(int) $index]['total'] = number_format($total, 2, '.', '');

        InvoiceLineItem::where('id', $row['id'])->update([
            'description' => $row['description'] ?? '',
            'quantity' => $qty,
            'unit_price' => $price,
            'total' => $total,
        ]);

        $this->refreshInvoice();
    }

    public function removeLine(int $index): void
    {
        $row = $this->lines[$index] ?? null;
        if (! $row) {
            return;
        }

        if (! empty($row['id'])) {
            InvoiceLineItem::where('id', $row['id'])->delete();
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->refreshInvoice();
    }

    public function getSubtotalProperty(): string
    {
        $sum = 0;
        foreach ($this->lines as $line) {
            $sum += (float) ($line['total'] ?? 0);
        }
        return number_format($sum, 2, '.', '');
    }

    private function refreshInvoice(): void
    {
        $this->invoice->recalculateFromLineItems();
        $this->invoice->refresh();
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->invoice->lineItems()->max('sort_order')) + 1;
    }

    public function render()
    {
        return view('livewire.invoice-line-items-manager');
    }
}
