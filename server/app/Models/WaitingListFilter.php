<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WaitingListFilter extends Model
{
    protected $table = 'waiting_list_filters';

    protected $fillable = [
        'label',
        'service_group',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active filters in display order. */
    public static function ordered(): \Illuminate\Support\Collection
    {
        return static::active()->orderBy('sort_order')->orderBy('label')->get();
    }
}
