<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'invoice_number',
        'share_token',
        'status',
        'subtotal',
        'tax',
        'credits_total',
        'total',
        'issued_at',
        'due_at',
        'paid_at',
        'sent_at',
        'is_payment_plan',
        'payment_plan_installments',
        'payment_plan_amount',
        'cc_fee_rate',
        'payment_plan_started_at',
        'payment_plan_payments_made',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->share_token)) {
                $invoice->share_token = substr(bin2hex(random_bytes(3)), 0, 6);
            }
            if (empty($invoice->invoice_number)) {
                $latest = static::max('id') ?? 0;
                $invoice->invoice_number = 'INV-' . str_pad($latest + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getPublicUrl(): string
    {
        return url("/invoice/{$this->share_token}");
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'credits_total' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'date',
            'sent_at' => 'datetime',
            'is_payment_plan' => 'boolean',
            'payment_plan_amount' => 'decimal:2',
            'cc_fee_rate' => 'decimal:4',
            'payment_plan_started_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(InvoiceCredit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function recalculateTotal(): void
    {
        $this->credits_total = $this->credits()->sum('amount');
        $this->total = $this->subtotal + $this->tax - $this->credits_total;
        $this->save();
    }

    /**
     * Drive the invoice subtotal from its line items, then recompute the total
     * (factoring tax and credits) (issue #30).
     */
    public function recalculateFromLineItems(): void
    {
        $this->subtotal = $this->lineItems()->sum('total');
        $this->recalculateTotal();
    }

    /** Total received against this invoice. */
    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /** Outstanding balance after credits and payments. */
    public function balanceDue(): float
    {
        return round((float) $this->total - $this->amountPaid(), 2);
    }
}
