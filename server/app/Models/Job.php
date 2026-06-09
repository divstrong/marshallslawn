<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    /** Hard ceiling for a single job's running timer (issue: 12h max per day). */
    public const MAX_TIMER_HOURS = 12;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'property_id',
        'estimate_id',
        'recurring_job_template_id',
        'crew_id',
        'title',
        'description',
        'status',
        'type',
        'waiting_list',
        'priority',
        'scheduled_date',
        'completed_date',
        'started_at',
        'finished_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'waiting_list' => 'boolean',
        ];
    }

    /**
     * Stop a running job timer. The finish time is capped to at most
     * MAX_TIMER_HOURS after it started, so a timer left running by accident
     * never logs more than the daily maximum.
     */
    public function stopTimer(?\Illuminate\Support\Carbon $at = null): void
    {
        if (! $this->started_at) {
            return;
        }

        $at ??= now();
        $cap = $this->started_at->copy()->addHours(self::MAX_TIMER_HOURS);
        $finish = $at->lessThan($cap) ? $at : $cap;

        $this->update([
            'status' => 'completed',
            'finished_at' => $finish,
            'completed_date' => $finish->toDateString(),
        ]);
    }

    /** A job whose timer is currently running (started but not finished). */
    public function isTimerRunning(): bool
    {
        return $this->started_at !== null && $this->finished_at === null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function crew(): BelongsTo
    {
        return $this->belongsTo(Crew::class);
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringJobTemplate::class, 'recurring_job_template_id');
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(JobMedia::class);
    }

    public function jobServices(): HasMany
    {
        return $this->hasMany(JobService::class)->orderBy('sort_order');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function chemicalLogs(): HasMany
    {
        return $this->hasMany(ChemicalLog::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
