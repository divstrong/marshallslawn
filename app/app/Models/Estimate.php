<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Estimate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'property_id',
        'estimate_number',
        'share_token',
        'status',
        'subtotal',
        'tax',
        'total',
        'valid_until',
        'notes',
        'sent_at',
        'accepted_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Estimate $estimate) {
            if (empty($estimate->share_token)) {
                $estimate->share_token = substr(bin2hex(random_bytes(3)), 0, 6);
            }
            if (empty($estimate->estimate_number)) {
                $latest = static::max('id') ?? 0;
                $estimate->estimate_number = 'EST-' . str_pad($latest + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getPublicUrl(): string
    {
        return url("/estimate/{$this->share_token}");
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(EstimateLineItem::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
