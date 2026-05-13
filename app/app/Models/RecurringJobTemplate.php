<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringJobTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'property_id',
        'service_id',
        'crew_id',
        'title',
        'interval_days',
        'preferred_day_of_week',
        'season_start_month',
        'season_end_month',
        'start_date',
        'end_date',
        'next_generation_date',
        'active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_generation_date' => 'date',
            'active' => 'boolean',
            'interval_days' => 'integer',
            'preferred_day_of_week' => 'integer',
            'season_start_month' => 'integer',
            'season_end_month' => 'integer',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'recurring_job_template_id');
    }
}
