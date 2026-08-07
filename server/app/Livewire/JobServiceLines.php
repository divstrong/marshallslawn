<?php

namespace App\Livewire;

use App\Models\Job;
use App\Models\JobService;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Estimate-style service editor for a job (Description / Qty / Rate / Total).
 * One component drives both create and edit:
 *
 *  - Edit ($jobId set): each change is persisted straight to job_services, like
 *    the invoice line-item manager.
 *  - Create ($draftId set, no job yet): rows are held in memory and mirrored to a
 *    short-lived cache entry keyed by the draft id. JobFromFormCreator reads that
 *    entry when the job is saved, so the same grid works inside the Filament
 *    create form / customer modal without a repeater.
 *
 * A blank rate means TBD (the office prices it later); the line total stays blank
 * and doesn't count toward the job total.
 */
class JobServiceLines extends Component
{
    public ?int $jobId = null;

    public ?string $draftId = null;

    public string $serviceSearch = '';

    public bool $showServiceDropdown = false;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    /** Monotonic key for draft rows (no DB id yet). */
    public int $seq = 0;

    public const DRAFT_TTL_MINUTES = 120;

    public function mount(?int $jobId = null, ?string $draftId = null): void
    {
        $this->jobId = $jobId;
        $this->draftId = $draftId;

        if ($this->isEdit()) {
            $this->loadFromJob();
        }
    }

    private function isEdit(): bool
    {
        return $this->jobId !== null;
    }

    public static function draftCacheKey(string $draftId): string
    {
        return 'job-service-draft:' . $draftId;
    }

    private function loadFromJob(): void
    {
        $job = Job::find($this->jobId);
        if (! $job) {
            return;
        }

        $this->lines = $job->jobServices()
            ->with('service:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (JobService $line) => [
                'key' => ++$this->seq,
                'id' => (int) $line->id,
                'service_id' => $line->service_id ? (int) $line->service_id : null,
                'description' => $line->description ?: ($line->service?->name ?? ''),
                'quantity' => number_format((float) ($line->quantity ?: 1), 2, '.', ''),
                'unit_price' => $line->unit_price === null ? '' : number_format((float) $line->unit_price, 2, '.', ''),
            ])
            ->all();
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) {
            return collect();
        }

        return Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                    ->orWhere('full_name', 'like', "%{$this->serviceSearch}%")
                    ->orWhere('code', 'like', "%{$this->serviceSearch}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'default_price']);
    }

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) {
            return;
        }

        // No standard rate → start the line as TBD rather than a misleading $0.
        $rate = $service->default_price !== null ? number_format((float) $service->default_price, 2, '.', '') : '';

        $this->appendLine([
            'service_id' => (int) $service->id,
            'description' => $service->name,
            'quantity' => '1.00',
            'unit_price' => $rate,
        ]);

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
    }

    public function addCustomLine(): void
    {
        $this->appendLine([
            'service_id' => null,
            'description' => '',
            'quantity' => '1.00',
            'unit_price' => '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function appendLine(array $attrs): void
    {
        $row = array_merge([
            'key' => ++$this->seq,
            'id' => null,
            'service_id' => null,
            'description' => '',
            'quantity' => '1.00',
            'unit_price' => '',
        ], $attrs);

        if ($this->isEdit()) {
            $row['id'] = $this->createRow($row);
        }

        $this->lines[] = $row;
        $this->persistDraft();
    }

    public function updatedLines($value, $key): void
    {
        [$index] = array_pad(explode('.', $key, 2), 2, null);
        $index = (int) $index;
        $row = $this->lines[$index] ?? null;
        if (! $row) {
            return;
        }

        // Normalise quantity to at least a number.
        $this->lines[$index]['quantity'] = number_format((float) ($row['quantity'] ?: 0), 2, '.', '');

        if ($this->isEdit() && ! empty($row['id'])) {
            JobService::where('id', $row['id'])->update($this->columnsFor($this->lines[$index]));
        }

        $this->persistDraft();
    }

    public function removeLine(int $index): void
    {
        $row = $this->lines[$index] ?? null;
        if (! $row) {
            return;
        }

        if ($this->isEdit() && ! empty($row['id'])) {
            JobService::where('id', $row['id'])->delete();
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->persistDraft();
    }

    /** Live job total — sum of priced lines only (TBD lines don't count). */
    public function getJobTotalProperty(): string
    {
        $sum = 0;
        foreach ($this->lines as $line) {
            $sum += $this->lineTotal($line) ?? 0;
        }

        return number_format($sum, 2, '.', '');
    }

    public function getHasTbdLinesProperty(): bool
    {
        foreach ($this->lines as $line) {
            if ($this->lineTotal($line) === null) {
                return true;
            }
        }

        return false;
    }

    /** The total for a row, or null when its rate is TBD. */
    public function lineTotal(array $line): ?float
    {
        if (! filled($line['unit_price'] ?? null)) {
            return null;
        }

        return round((float) $line['quantity'] * (float) $line['unit_price'], 2);
    }

    /**
     * DB column values for a working row. A blank rate persists as null across
     * unit_price and price so the line reads as TBD everywhere.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function columnsFor(array $row): array
    {
        $tbd = ! filled($row['unit_price'] ?? null);
        $qty = (float) ($row['quantity'] ?: 1);

        return [
            'service_id' => $row['service_id'] ?: null,
            'description' => $row['description'] ?: null,
            'quantity' => $qty,
            'unit_price' => $tbd ? null : round((float) $row['unit_price'], 2),
            'price' => $tbd ? null : round($qty * (float) $row['unit_price'], 2),
        ];
    }

    private function createRow(array $row): int
    {
        return JobService::create(array_merge(
            ['job_id' => $this->jobId, 'sort_order' => $this->nextSortOrder()],
            $this->columnsFor($row),
        ))->id;
    }

    private function nextSortOrder(): int
    {
        return ((int) JobService::where('job_id', $this->jobId)->max('sort_order')) + 1;
    }

    /** Mirror the working rows into the draft cache (create flow only). */
    private function persistDraft(): void
    {
        // On edit the lines are already in the database — but the job's derived
        // title is built from them, so it has to follow them.
        if ($this->isEdit()) {
            Job::find($this->jobId)?->refreshTitle();

            return;
        }

        if (! $this->draftId) {
            return;
        }

        $payload = array_map(fn ($row) => $this->columnsFor($row), array_values($this->lines));

        Cache::put(self::draftCacheKey($this->draftId), $payload, now()->addMinutes(self::DRAFT_TTL_MINUTES));
    }

    public function render()
    {
        return view('livewire.job-service-lines');
    }
}
