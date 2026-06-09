<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * Payment methods.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'cash' => 'Cash',
        'check' => 'Check',
        'card' => 'Credit / Debit Card',
        'ach' => 'ACH / Bank Transfer',
        'other' => 'Other',
    ];

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'amount',
        'method',
        'reference',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            // Inherit the customer from the invoice when not set explicitly.
            if (empty($payment->customer_id) && $payment->invoice_id) {
                $payment->customer_id = Invoice::whereKey($payment->invoice_id)->value('customer_id');
            }
            // Record who entered it (when in an authenticated context).
            if (empty($payment->recorded_by) && auth()->check()) {
                $payment->recorded_by = auth()->id();
            }
            if (empty($payment->paid_at)) {
                $payment->paid_at = now()->toDateString();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
