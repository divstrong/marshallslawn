<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An editable crew classification (Mowing / Spraying / Mulching / ...).
 *
 * Managed from Settings -> Crew Types. The `name` is the machine key stored
 * inside the `crews.type` JSON array; `label` is what the UI shows, so a type
 * can be relabelled without touching crew records.
 */
class CrewType extends Model
{
    protected $table = 'crew_types';

    protected $fillable = [
        'name',
        'label',
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

    /**
     * Ordered [name => label] map for checkbox lists, selects and filters.
     *
     * @return array<string, string>
     */
    public static function options(bool $activeOnly = true): array
    {
        return static::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'name')
            ->all();
    }

    /** Crews carrying this type. `crews.type` is a JSON array of names. */
    public function crews()
    {
        return Crew::query()->whereJsonContains('type', $this->name);
    }
}
