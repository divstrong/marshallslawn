<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use App\Support\ServiceGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;
    use HasTags;

    /** Hard ceiling for a single job's running timer (issue: 12h max per day). */
    public const MAX_TIMER_HOURS = 12;

    /**
     * How a job is priced. A service job is built from priced service lines that
     * sum to a scope; a quick job is a flat price plus notes for an existing
     * customer, with no services at all. The choice drives which fields the job
     * form offers.
     */
    public const KIND_SERVICE = 'service';

    public const KIND_QUICK = 'quick';

    /** @return array<string, string> */
    public static function kindOptions(): array
    {
        return [
            self::KIND_SERVICE => 'Service Job',
            self::KIND_QUICK => 'Quick Job',
        ];
    }

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
        'price',
        'recurring_job_template_id',
        'crew_id',
        'title',
        'description',
        'status',
        'type',
        'kind',
        'waiting_list',
        'priority',
        'estimated_minutes',
        'do_not_move',
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
            'do_not_move' => 'boolean',
            'estimated_minutes' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function isQuick(): bool
    {
        return $this->kind === self::KIND_QUICK;
    }

    /**
     * The job's display label. Nobody types this any more — the "Job title" input
     * was removed from every job form — so it is derived from what the job is:
     * its services for a service job, the first line of its notes for a quick
     * one. Everything downstream (mobile app, SMS/push, dispatch cards, time
     * logs) still reads `title`, so it always holds a sensible label.
     */
    public function deriveTitle(): string
    {
        if ($this->isQuick()) {
            $firstLine = trim(explode("\n", (string) ($this->notes ?? ''))[0]);

            return $firstLine !== ''
                ? \Illuminate\Support\Str::limit($firstLine, 60)
                : 'Quick job';
        }

        $names = $this->jobServices()
            ->with('service:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (JobService $line): ?string => $line->service?->name ?: $line->description)
            ->filter()
            ->values();

        return match (true) {
            $names->isEmpty() => 'Service job',
            $names->count() === 1 => (string) $names[0],
            default => $names[0] . ' +' . ($names->count() - 1) . ' more',
        };
    }

    /**
     * Recompute the derived title after the job's service lines change. Saved
     * quietly: this is a label refresh, not a change worth re-running the
     * observer's route sync and customer notifications over.
     */
    public function refreshTitle(): void
    {
        $title = $this->deriveTitle();

        if ($title !== $this->title) {
            $this->forceFill(['title' => $title])->saveQuietly();
        }
    }

    /**
     * The job's revenue total — the sum of its approved service line prices,
     * falling back to the direct price when there are no line items. Computed on
     * read so it always reflects the current lines (no denormalized column to
     * keep in sync). Each completed job's total feeds the crew revenue leaderboard.
     */
    public function total(): float
    {
        $lineSum = (float) $this->jobServices->sum('price');

        return $lineSum > 0 ? $lineSum : (float) ($this->price ?? 0);
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

    /**
     * High-level service bucket for the dashboard "Job Service Mix" — one of
     * the {@see ServiceGroup} constants (spraying / mulching / mowing /
     * projects).
     *
     * Prefers the group of a linked service (checked in Spray → Mulch →
     * Mowing priority so a multi-service job lands in its most specific
     * bucket) and falls back to keyword-matching the title + description when
     * no service is linked — the case for most jobs until tags are imported.
     */
    public function serviceGroup(): string
    {
        foreach ([ServiceGroup::SPRAYING, ServiceGroup::MULCHING, ServiceGroup::MOWING] as $bucket) {
            foreach ($this->jobServices as $jobService) {
                if ($jobService->service
                    && ServiceGroup::fromServiceGroup($jobService->service->service_group) === $bucket) {
                    return $bucket;
                }
            }
        }

        return ServiceGroup::classify(trim($this->title.' '.$this->description));
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
