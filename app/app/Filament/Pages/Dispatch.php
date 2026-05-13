<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Models\Crew;
use App\Models\RouteStop;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class Dispatch extends Page
{
    use ChecksResourceAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dispatch';

    private const CREW_PALETTE = [
        '#e00a35', // brand red
        '#2563eb', // blue
        '#16a34a', // green
        '#f59e0b', // amber
        '#7c3aed', // violet
        '#ec4899', // pink
        '#0891b2', // cyan
        '#f97316', // orange
    ];

    #[Url(as: 'date')]
    public ?string $date = null;

    /** @var array<int, int> */
    #[Url(as: 'crews')]
    public array $crewIds = [];

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    public ?int $selectedStopId = null;

    public ?int $selectedJobId = null;

    public function mount(): void
    {
        $this->date ??= now()->toDateString();
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    #[Computed]
    public function crewColorMap(): array
    {
        $crews = Crew::orderBy('id')->get(['id', 'name']);
        $map = [];
        foreach ($crews as $i => $crew) {
            $map[(int) $crew->id] = [
                'id' => (int) $crew->id,
                'name' => $crew->name,
                'color' => self::CREW_PALETTE[$i % count(self::CREW_PALETTE)],
            ];
        }
        return $map;
    }

    #[Computed]
    public function stops(): array
    {
        $crewMap = $this->crewColorMap();

        $rows = RouteStop::query()
            ->with([
                'property:id,address,city,state,zip,latitude,longitude',
                'customer:id,first_name,last_name,company_name,phone,email',
                'service:id,name,category',
                'job:id,title,priority',
                'route:id,name,route_date,crew_id',
                'route.crew:id,name',
            ])
            ->whereHas('route', function ($q) {
                $q->whereDate('route_date', $this->date);
                if (! empty($this->crewIds)) {
                    $q->whereIn('crew_id', $this->crewIds);
                }
            })
            ->whereHas('property', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->get();

        return $rows->map(function ($stop) use ($crewMap) {
            $crewId = (int) ($stop->route?->crew_id ?? 0);
            $color = $crewMap[$crewId]['color'] ?? '#6b7280';

            $customer = $stop->customer;
            $customerName = $customer
                ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    ?: ($customer->company_name ?? '—'))
                : '—';

            return [
                'id' => (int) $stop->id,
                'lat' => (float) $stop->property->latitude,
                'lng' => (float) $stop->property->longitude,
                'sort_order' => (int) $stop->sort_order,
                'status' => (string) $stop->status,
                'color' => $color,
                'crew_id' => $crewId,
                'crew_name' => $stop->route?->crew?->name,
                'route_id' => (int) $stop->route_id,
                'route_name' => $stop->route?->name,
                'customer_name' => $customerName,
                'customer_phone' => $customer?->phone,
                'address' => $stop->property?->address ?? '—',
                'city' => $stop->property?->city,
                'service_name' => $stop->service?->name,
                'job_title' => $stop->job?->title,
                'notes' => $stop->notes,
            ];
        })->all();
    }

    #[Computed]
    public function unroutedJobs(): array
    {
        $assignedJobIds = RouteStop::query()
            ->whereHas('route', fn ($q) => $q->whereDate('route_date', $this->date))
            ->whereNotNull('job_id')
            ->pluck('job_id')
            ->all();

        $rows = \App\Models\Job::query()
            ->with([
                'customer:id,first_name,last_name,company_name,phone,email',
                'property:id,address,city,state,zip,latitude,longitude',
                'recurringTemplate.service:id,name',
            ])
            ->whereDate('scheduled_date', $this->date)
            ->whereNotIn('id', $assignedJobIds)
            ->whereHas('property', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->get();

        return $rows->map(function ($job) {
            $customer = $job->customer;
            $customerName = $customer
                ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    ?: ($customer->company_name ?? '—'))
                : '—';

            return [
                'id' => (int) $job->id,
                'kind' => 'job',
                'lat' => (float) $job->property->latitude,
                'lng' => (float) $job->property->longitude,
                'color' => '#9ca3af', // neutral gray for unrouted
                'priority' => $job->priority,
                'status' => 'unrouted',
                'customer_name' => $customerName,
                'customer_phone' => $customer?->phone,
                'address' => $job->property?->address ?? '—',
                'city' => $job->property?->city,
                'service_name' => $job->recurringTemplate?->service?->name,
                'job_title' => $job->title,
                'sort_order' => null,
            ];
        })->all();
    }

    #[Computed]
    public function selectedUnroutedJob(): ?array
    {
        if (! $this->selectedJobId) {
            return null;
        }
        foreach ($this->unroutedJobs as $j) {
            if ($j['id'] === $this->selectedJobId) {
                return $j;
            }
        }
        return null;
    }

    #[Computed]
    public function unmappedStops(): array
    {
        $rows = RouteStop::query()
            ->with(['customer', 'property', 'route'])
            ->whereHas('route', function ($q) {
                $q->whereDate('route_date', $this->date);
                if (! empty($this->crewIds)) {
                    $q->whereIn('crew_id', $this->crewIds);
                }
            })
            ->where(function ($q) {
                $q->whereDoesntHave('property')
                  ->orWhereHas('property', fn ($p) => $p->whereNull('latitude')->orWhereNull('longitude'));
            })
            ->orderBy('sort_order')
            ->get();

        return $rows->map(function ($stop) {
            $c = $stop->customer;
            $name = $c
                ? trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')) ?: ($c->company_name ?? '—')
                : '—';
            return [
                'id' => (int) $stop->id,
                'customer_name' => $name,
                'address' => $stop->property?->address ?? '(no address)',
                'route_name' => $stop->route?->name,
            ];
        })->all();
    }

    #[Computed]
    public function selectedStop(): ?array
    {
        if (! $this->selectedStopId) {
            return null;
        }
        foreach ($this->stops as $stop) {
            if ($stop['id'] === $this->selectedStopId) {
                return $stop;
            }
        }
        return null;
    }

    #[Computed]
    public function summary(): array
    {
        $stops = $this->stops;
        $byCrew = [];
        $byStatus = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'skipped' => 0];

        foreach ($stops as $s) {
            $cid = $s['crew_id'];
            if (! isset($byCrew[$cid])) {
                $byCrew[$cid] = [
                    'crew_id' => $cid,
                    'crew_name' => $s['crew_name'] ?? 'Unassigned',
                    'color' => $s['color'],
                    'count' => 0,
                ];
            }
            $byCrew[$cid]['count']++;
            $byStatus[$s['status']] = ($byStatus[$s['status']] ?? 0) + 1;
        }

        return [
            'total' => count($stops),
            'by_crew' => array_values($byCrew),
            'by_status' => $byStatus,
        ];
    }

    public function selectStop(int $id): void
    {
        $this->selectedStopId = $id;
        $this->selectedJobId = null;
    }

    public function selectJob(int $id): void
    {
        $this->selectedJobId = $id;
        $this->selectedStopId = null;
    }

    public function clearSelection(): void
    {
        $this->selectedStopId = null;
        $this->selectedJobId = null;
    }

    public function toggleCrew(int $id): void
    {
        $crewIds = array_map('intval', $this->crewIds);
        if (in_array($id, $crewIds, true)) {
            $this->crewIds = array_values(array_filter($crewIds, fn ($cid) => $cid !== $id));
        } else {
            $this->crewIds = [...$crewIds, $id];
        }
        $this->emitStopsUpdated();
    }

    public function shiftDate(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
        $this->selectedStopId = null;
        $this->emitStopsUpdated();
    }

    public function updatedDate(): void
    {
        $this->selectedStopId = null;
        $this->emitStopsUpdated();
    }

    public function updatedStatusFilter(): void
    {
        $this->emitStopsUpdated();
    }

    public function markStopStatus(int $id, string $status): void
    {
        $allowed = ['pending', 'in_progress', 'completed', 'skipped'];
        if (! in_array($status, $allowed, true)) {
            return;
        }

        $stop = RouteStop::find($id);
        if (! $stop) {
            return;
        }

        $stop->status = $status;
        if ($status === 'completed') {
            $stop->completed_at = now();
        } elseif ($status === 'pending' || $status === 'in_progress') {
            $stop->completed_at = null;
        }
        $stop->save();

        unset($this->stops, $this->selectedStop, $this->summary);
        $this->emitStopsUpdated();
    }

    public function getGoogleMapsApiKey(): ?string
    {
        return config('services.google.maps_key');
    }

    private function emitStopsUpdated(): void
    {
        $this->dispatch(
            'dispatch:stops-updated',
            stops: $this->stops,
            unroutedJobs: $this->unroutedJobs,
            crewColors: $this->crewColorMap,
        );
    }
}
