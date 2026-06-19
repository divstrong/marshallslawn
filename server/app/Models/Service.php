<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    /**
     * High-level service groups surfaced as dispatch filters (issue #15).
     *
     * @var array<string, string>
     */
    public const GROUPS = [
        'mowing' => 'Mowing',
        'spraying' => 'Spraying',
        'mulching' => 'Mulching',
        'other' => 'Other',
    ];

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
        'icon_path',
        'service_group',
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

    /** Selectable variations of this service, e.g. "Hand Laid" vs "Machine Blown" (issue #21). */
    public function options(): HasMany
    {
        return $this->hasMany(ServiceOption::class)->orderBy('sort_order')->orderBy('name');
    }

    /** Active options only, for selection UIs. */
    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * Public URL for the optional service icon, or null when none is set.
     */
    public function iconUrl(): ?string
    {
        if (empty($this->icon_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->icon_path);
    }
}
