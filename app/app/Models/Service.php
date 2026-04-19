<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legacy_id',
        'name',
        'code',
        'parent_service',
        'full_name',
        'description',
        'estimate_description',
        'invoice_description',
        'category',
        'default_price',
        'minimum_amount',
        'unit',
        'service_mode',
        'is_active',
        'track_chemicals',
        'show_in_snow',
        'list_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'track_chemicals' => 'boolean',
            'show_in_snow' => 'boolean',
        ];
    }

    public function estimateLineItems(): HasMany
    {
        return $this->hasMany(EstimateLineItem::class);
    }
}
