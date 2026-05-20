<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route_date',
        'crew_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'route_date' => 'date',
        ];
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('sort_order');
    }

    public function foreman(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            Crew::class,
            'id',          // Crew.id
            'id',          // Employee.id
            'crew_id',     // Route.crew_id
            'foreman_id'   // Crew.foreman_id
        );
    }
}
