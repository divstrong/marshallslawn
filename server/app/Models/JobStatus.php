<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    protected $table = 'job_statuses';

    protected $fillable = [
        'name',
        'label',
        'color',
        'sort_order',
        'is_protected',
    ];

    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Filament badge colors available for a status. */
    public const COLORS = [
        'gray' => 'Gray',
        'primary' => 'Blue',
        'info' => 'Sky',
        'success' => 'Green',
        'warning' => 'Amber',
        'danger' => 'Red',
    ];

    /**
     * Ordered [name => label] map for select inputs / filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::orderBy('sort_order')->orderBy('label')->pluck('label', 'name')->all();
    }

    /**
     * Ordered [name => color] map for badge coloring.
     *
     * @return array<string, string>
     */
    public static function colorMap(): array
    {
        return static::pluck('color', 'name')->all();
    }
}
