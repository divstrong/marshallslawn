<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\Crew;
use App\Models\Job;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Service;
use App\Models\Setting;
use App\Services\GeocodingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dispatch')]
#[Title('Dispatch')]
class DispatchBoard extends Component
{
    use WithFileUploads;

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

    /** A foreman GPS ping is treated as "live" within this many minutes. */
    private const LIVE_WINDOW_MINUTES = 15;

    #[Url(as: 'date')]
    public ?string $date = null;

    /** @var array<int, int> */
    #[Url(as: 'crews')]
    public array $crewIds = [];

    #[Url(as: 'status')]
    public ?string $statusFilter = null;

    /**
     * Selected service-group filters (Spraying / Mowing / Mulching). Empty = show all.
     *
     * @var array<int, string>
     */
    #[Url(as: 'services')]
    public array $serviceGroups = [];

    public ?int $selectedStopId = null;

    public ?int $selectedJobId = null;

    public ?int $selectedForemanId = null;

    /** Stop id awaiting Skip confirmation in the modal. */
    public ?int $confirmSkipStopId = null;

    /** Whether the job services/notes modal is open. */
    public bool $showServicesModal = false;

    /** Right-side chat panel state. */
    public bool $chatPanelOpen = false;

    #[Url(as: 'gps')]
    public bool $showGps = true;

    /**
     * Crews the user has hidden from the board (persisted per-user, issue #24).
     *
     * @var array<int, int>
     */
    public array $hiddenCrewIds = [];

    /** Board layout: 'map' (map + side list) or 'list' (full-width job cards). */
    public string $viewMode = 'map';

    /** List-view range: 'day' (selected day) or 'week' (Mon–Sat of that week). */
    public string $listRange = 'day';

    /** Free-text filter for the List view (matches customer / property / service). */
    public string $listSearch = '';

    /** Composer state for the foreman chat panel. */
    public string $chatBody = '';

    public $chatAttachment = null;

    /** New Job modal (issue #55): create a job on the fly without leaving the board. */
    public bool $showNewJobModal = false;

    public string $newJobCustomerSearch = '';

    /**
     * @var array{customer_id: ?int, property_id: ?int, kind: string, price: ?string,
     *     notes: ?string, priority: string, scheduled_date: ?string, crew_id: ?int,
     *     service_ids: array<int, int>}
     */
    public array $newJob = [
        'customer_id' => null,
        'property_id' => null,
        'kind' => Job::KIND_SERVICE,
        'price' => null,
        'notes' => null,
        'priority' => 'normal',
        'scheduled_date' => null,
        'crew_id' => null,
        'service_ids' => [],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->hasAccessTo('Dispatch'), 403);

        $this->date ??= now()->toDateString();

        // Restore this user's saved crew visibility + view mode (issue #24).
        $prefs = $user->dispatch_preferences ?? [];
        $this->hiddenCrewIds = array_values(array_map('intval', $prefs['hidden_crews'] ?? []));
        $storedMode = $prefs['view_mode'] ?? 'map';
        $this->viewMode = in_array($storedMode, ['map', 'list', 'month'], true) ? $storedMode : 'map';
        $this->listRange = ($prefs['list_range'] ?? 'day') === 'week' ? 'week' : 'day';
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

    /**
     * The crews the board is showing: every crew minus the ones unticked in the
     * Crews dropdown, narrowed further by an explicit ?crews= URL filter when
     * one is present. Every view filters on this single list, so unticking a
     * crew drops it from the map, the agenda and the month grid alike.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function activeCrewIds(): array
    {
        $ids = array_diff(
            array_keys($this->crewColorMap()),
            array_map('intval', $this->hiddenCrewIds),
        );

        if (! empty($this->crewIds)) {
            $ids = array_intersect($ids, array_map('intval', $this->crewIds));
        }

        return array_values($ids);
    }

    /** Whether the "Service Icons" admin toggle is enabled. */
    #[Computed]
    public function serviceIconsEnabled(): bool
    {
        return Setting::bool('dispatch_service_icons');
    }

    #[Computed]
    public function stops(): array
    {
        $crewMap = $this->crewColorMap();

        $rows = RouteStop::query()
            ->with([
                'property:id,address,city,state,zip,latitude,longitude',
                'customer:id,first_name,last_name,company_name,phone,email',
                'service:id,name,category,icon_path,service_group',
                'job:id,title,priority',
                'job.jobServices.service:id,name,icon_path,service_group',
                'route:id,name,route_date,crew_id',
                'route.crew:id,name',
            ])
            ->whereHas('route', function ($q) {
                $q->whereDate('route_date', $this->date);
                $q->whereIn('crew_id', $this->activeCrewIds);
            })
            ->whereHas('property', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->get();

        $rows = $rows->map(function ($stop) use ($crewMap) {
            $crewId = (int) ($stop->route?->crew_id ?? 0);
            $color = $crewMap[$crewId]['color'] ?? '#6b7280';

            $customer = $stop->customer;
            $customerName = $customer
                ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    ?: ($customer->company_name ?? '—'))
                : '—';

            // Services on a stop come from its explicit service, plus any services
            // attached to the underlying job (drives the icon + group filters).
            $services = collect();
            if ($stop->service) {
                $services->push($stop->service);
            }
            foreach ($stop->job?->jobServices ?? [] as $js) {
                if ($js->service) {
                    $services->push($js->service);
                }
            }
            $meta = $this->serviceMeta($services);

            return [
                'id' => (int) $stop->id,
                'job_id' => $stop->job_id ? (int) $stop->job_id : null,
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
                'service_groups' => $meta['groups'],
                'icon_url' => $meta['icon_url'],
                'job_title' => $stop->job?->title,
                'notes' => $stop->notes,
            ];
        });

        if (! empty($this->serviceGroups)) {
            $rows = $rows->filter(fn ($s) => array_intersect($s['service_groups'], $this->serviceGroups) !== []);
        }

        return $rows->values()->all();
    }

    /**
     * Derive the distinct service groups and a representative icon from a set of services.
     *
     * @param  \Illuminate\Support\Collection<int, Service>  $services
     * @return array{groups: array<int, string>, icon_url: ?string}
     */
    private function serviceMeta($services): array
    {
        $groups = [];
        $iconUrl = null;

        foreach ($services as $service) {
            if (! empty($service->service_group)) {
                $groups[$service->service_group] = true;
            }
            if ($iconUrl === null && ! empty($service->icon_path)) {
                $iconUrl = $service->iconUrl();
            }
        }

        return [
            'groups' => array_keys($groups),
            'icon_url' => $iconUrl,
        ];
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
                'jobServices.service:id,name,icon_path,service_group',
                'recurringTemplate.service:id,name,icon_path,service_group',
            ])
            ->whereDate('scheduled_date', $this->date)
            ->whereNotIn('id', $assignedJobIds)
            ->whereHas('property', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->get();

        $rows = $rows->map(function ($job) {
            $customer = $job->customer;
            $customerName = $customer
                ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    ?: ($customer->company_name ?? '—'))
                : '—';

            $services = $job->jobServices->pluck('service')->filter();
            if ($services->isEmpty() && $job->recurringTemplate?->service) {
                $services = collect([$job->recurringTemplate->service]);
            }
            $meta = $this->serviceMeta($services);

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
                'service_name' => $services->first()?->name ?? $job->recurringTemplate?->service?->name,
                'service_groups' => $meta['groups'],
                'icon_url' => $meta['icon_url'],
                'job_title' => $job->title,
                'sort_order' => null,
            ];
        });

        if (! empty($this->serviceGroups)) {
            $rows = $rows->filter(fn ($j) => array_intersect($j['service_groups'], $this->serviceGroups) !== []);
        }

        return $rows->values()->all();
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
    public function foremanPins(): array
    {
        if (! $this->showGps) {
            return [];
        }

        $crewMap = $this->crewColorMap;
        if (empty($crewMap)) {
            return [];
        }

        $crews = Crew::with(['foreman.latestLocation'])
            ->whereIn('id', $this->activeCrewIds)
            ->whereNotNull('foreman_id')
            ->get();

        // Stop centroids per crew for the current date (so the foreman appears near their route)
        $stopCentroids = [];
        foreach ($this->stops as $stop) {
            $cid = $stop['crew_id'];
            if (! isset($stopCentroids[$cid])) {
                $stopCentroids[$cid] = ['lat' => 0.0, 'lng' => 0.0, 'n' => 0];
            }
            $stopCentroids[$cid]['lat'] += $stop['lat'];
            $stopCentroids[$cid]['lng'] += $stop['lng'];
            $stopCentroids[$cid]['n']++;
        }

        // Richmond fallback
        $fallbackLat = 37.5407;
        $fallbackLng = -77.4360;

        // Unread chat counts (messages from foremen the office hasn't read).
        $unreadChat = ChatMessage::query()
            ->where('sender', ChatMessage::SENDER_FOREMAN)
            ->whereNull('read_at')
            ->selectRaw('employee_id, count(*) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id');

        $pins = [];
        foreach ($crews as $crew) {
            $foreman = $crew->foreman;
            if (! $foreman) {
                continue;
            }

            $location = $foreman->latestLocation;
            $hasLocation = $location !== null;
            $isLive = false;
            $lastSeen = null;

            if ($hasLocation) {
                $lat = (float) $location->latitude;
                $lng = (float) $location->longitude;
                $isLive = $location->recorded_at->greaterThan(
                    now()->subMinutes(self::LIVE_WINDOW_MINUTES)
                );
                $lastSeen = $location->recorded_at->diffForHumans();
            } else {
                $offsetLat = ((((int) $crew->id) * 17) % 100 - 50) / 10000;
                $offsetLng = ((((int) $crew->id) * 23) % 100 - 50) / 10000;

                if (isset($stopCentroids[$crew->id]) && $stopCentroids[$crew->id]['n'] > 0) {
                    $c = $stopCentroids[$crew->id];
                    $lat = $c['lat'] / $c['n'] + $offsetLat;
                    $lng = $c['lng'] / $c['n'] + $offsetLng;
                } else {
                    $lat = $fallbackLat + $offsetLat * 5;
                    $lng = $fallbackLng + $offsetLng * 5;
                }
            }

            $name = trim(($foreman->first_name ?? '') . ' ' . ($foreman->last_name ?? ''))
                ?: ($foreman->name ?? 'Foreman');
            $initials = strtoupper(substr($foreman->first_name ?? '', 0, 1) . substr($foreman->last_name ?? '', 0, 1));
            if ($initials === '') {
                $initials = strtoupper(substr($name, 0, 2));
            }

            $pins[] = [
                'id' => (int) $foreman->id,
                'crew_id' => (int) $crew->id,
                'crew_name' => $crew->name,
                'name' => $name,
                'initials' => $initials ?: '?',
                'color' => $crewMap[$crew->id]['color'] ?? '#6b7280',
                'phone' => $foreman->mobile_phone ?? $foreman->phone,
                'lat' => round($lat, 7),
                'lng' => round($lng, 7),
                'stops_today' => $stopCentroids[$crew->id]['n'] ?? 0,
                'has_location' => $hasLocation,
                'is_live' => $isLive,
                'last_seen' => $lastSeen,
                'unread_chat' => (int) ($unreadChat[$foreman->id] ?? 0),
            ];
        }

        return $pins;
    }

    #[Computed]
    public function selectedForeman(): ?array
    {
        if (! $this->selectedForemanId) {
            return null;
        }
        foreach ($this->foremanPins as $f) {
            if ($f['id'] === $this->selectedForemanId) {
                return $f;
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
                $q->whereIn('crew_id', $this->activeCrewIds);
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
                'property_id' => $stop->property?->id,
                'customer_name' => $name,
                'address' => $stop->property?->address ?? '(no address)',
                'route_name' => $stop->route?->name,
            ];
        })->all();
    }

    /** Inline status message for the geocode "Fix" actions (no Filament toasts in this layout). */
    public ?string $geocodeNotice = null;

    /**
     * Geocode the property behind a single unmapped stop on demand — the in-app
     * equivalent of `php artisan properties:geocode --missing` for one record.
     */
    public function geocodeStopProperty(int $stopId): void
    {
        $stop = RouteStop::with('property')->find($stopId);
        $property = $stop?->property;

        if (! $property) {
            $this->geocodeNotice = 'That stop has no property address to locate.';
            return;
        }

        try {
            $ok = app(GeocodingService::class)->geocodeProperty($property);
        } catch (\Throwable $e) {
            $ok = false;
        }

        if ($ok) {
            $this->geocodeNotice = null;
            $this->refreshAfterGeocode();
        } else {
            $this->geocodeNotice = "Couldn't locate \"{$property->address}\". Check the address is valid and that the Maps API key is configured.";
        }
    }

    /** Geocode every unmapped property for the current day/crew filter in one click. */
    public function geocodeAllUnmapped(): void
    {
        $stops = RouteStop::with('property')
            ->whereHas('route', function ($q) {
                $q->whereDate('route_date', $this->date);
                $q->whereIn('crew_id', $this->activeCrewIds);
            })
            ->whereHas('property', fn ($p) => $p->whereNull('latitude')->orWhereNull('longitude'))
            ->get();

        $geocoder = app(GeocodingService::class);
        $located = 0;
        $failed = 0;
        $seen = [];

        foreach ($stops as $stop) {
            $property = $stop->property;
            if (! $property || isset($seen[$property->id])) {
                continue;
            }
            $seen[$property->id] = true;

            try {
                $geocoder->geocodeProperty($property) ? $located++ : $failed++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $this->geocodeNotice = $failed > 0
            ? "Located {$located}; {$failed} couldn't be geocoded — check those addresses."
            : null;

        $this->refreshAfterGeocode();
    }

    /** Recompute map data after coordinates change so pins/lists refresh immediately. */
    private function refreshAfterGeocode(): void
    {
        unset($this->stops, $this->unroutedJobs, $this->unmappedStops, $this->summary, $this->selectedStop, $this->foremanPins);
        $this->emitStopsUpdated();
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

    /** The date window the List view covers: [start, end] inclusive. */
    private function listDateRange(): array
    {
        $date = Carbon::parse($this->date);

        if ($this->listRange === 'week') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY);
            return [$start->toDateString(), $start->copy()->addDays(5)->toDateString()]; // Mon–Sat
        }

        return [$date->toDateString(), $date->toDateString()];
    }

    /** Header label for the List view (e.g. "Monday, June 23" or "June 23 – 28, 2026"). */
    #[Computed]
    public function listRangeLabel(): string
    {
        [$start, $end] = $this->listDateRange();
        $startC = Carbon::parse($start);

        if ($this->listRange !== 'week') {
            return $startC->format('l, F j, Y');
        }

        $endC = Carbon::parse($end);
        $left = $startC->format('M j');
        $right = $startC->month === $endC->month
            ? $endC->format('j, Y')
            : $endC->format('M j, Y');

        return "{$left} – {$right}";
    }

    /**
     * Day-by-day agenda for the List view: each day holds one clickable card per
     * job (route stop), built from real route stops (respecting the active crew /
     * status / service filters). Crew is shown as a badge so the crew toggles
     * above act purely as a filter rather than a grouping.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function listDays(): array
    {
        $crewMap = $this->crewColorMap();
        [$start, $end] = $this->listDateRange();

        $stops = RouteStop::query()
            ->with([
                'customer:id,first_name,last_name,company_name',
                'property:id,address,city',
                'service:id,name,service_group',
                'job:id,title',
                'job.jobServices.service:id,name,service_group',
                'route:id,name,route_date,crew_id',
            ])
            ->whereHas('route', function ($q) use ($start, $end) {
                $q->whereDate('route_date', '>=', $start)
                  ->whereDate('route_date', '<=', $end);
                $q->whereIn('crew_id', $this->activeCrewIds);
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->get();

        $search = trim(mb_strtolower($this->listSearch));

        // Group stops by date; each stop renders as its own job card. Crews are
        // already narrowed by the query above.
        $byDay = [];
        foreach ($stops as $stop) {
            if (! empty($this->serviceGroups)
                && array_intersect($this->stopServiceGroups($stop), $this->serviceGroups) === []) {
                continue;
            }
            if ($search !== '' && ! $this->stopMatchesSearch($stop, $search)) {
                continue;
            }
            $dateKey = $stop->route?->route_date?->toDateString();
            if (! $dateKey) {
                continue;
            }
            $byDay[$dateKey][] = $stop;
        }

        // Emit every day in the range (including empty ones) so the week reads
        // continuously — but while searching, drop empty days so matches read tightly.
        $days = [];
        $today = now()->toDateString();
        $cursor = Carbon::parse($start);
        $endC = Carbon::parse($end);

        while ($cursor <= $endC) {
            $dayC = $cursor->copy();
            $cursor->addDay();

            $dateKey = $dayC->toDateString();

            $jobs = [];
            foreach ($byDay[$dateKey] ?? [] as $stop) {
                $jobs[] = $this->buildStopCard($stop, $crewMap);
            }
            // Keep each crew's stops together, in route order.
            usort($jobs, fn ($a, $b) => [$a['crew_id'], $a['sort_order']] <=> [$b['crew_id'], $b['sort_order']]);

            if ($search !== '' && $jobs === []) {
                continue;
            }

            $days[] = [
                'date' => $dateKey,
                'weekday' => strtoupper($dayC->format('D')),
                'day_num' => $dayC->format('j'),
                'is_today' => $dateKey === $today,
                'jobs' => $jobs,
            ];
        }

        return $days;
    }

    /** Human label for the Month grid, e.g. "July 2026". */
    #[Computed]
    public function monthLabel(): string
    {
        return Carbon::parse($this->date)->format('F Y');
    }

    /**
     * Calendar grid (whole weeks, Sun–Sat) for the month containing the selected
     * date, with the number of scheduled stops per day so the office can see the
     * month's workload at a glance and drill into any day.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function monthDays(): array
    {
        $anchor = Carbon::parse($this->date)->startOfMonth();
        $gridStart = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $crewMap = $this->crewColorMap();

        // Stops per day AND crew (respecting the crew filter), so
        // each day is broken out by crew with its count + color rather than one lump sum.
        $rows = RouteStop::query()
            ->join('routes', 'routes.id', '=', 'route_stops.route_id')
            ->whereBetween('routes.route_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->whereIn('routes.crew_id', $this->activeCrewIds)
            ->selectRaw('DATE(routes.route_date) as d, routes.crew_id as crew_id, COUNT(*) as c')
            ->groupBy('d', 'routes.crew_id')
            ->get();

        // Group the per-crew tallies under each day.
        $byDay = [];
        foreach ($rows as $r) {
            $cid = (int) $r->crew_id;
            $byDay[$r->d][] = [
                'crew_id' => $cid,
                'name' => $crewMap[$cid]['name'] ?? 'Unassigned',
                'color' => $crewMap[$cid]['color'] ?? '#6b7280',
                'count' => (int) $r->c,
            ];
        }

        $today = now()->toDateString();
        $days = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $key = $cursor->toDateString();
            $crews = $byDay[$key] ?? [];
            usort($crews, fn ($a, $b) => $a['crew_id'] <=> $b['crew_id']);

            $days[] = [
                'date' => $key,
                'day_num' => (int) $cursor->format('j'),
                'in_month' => $cursor->month === $anchor->month,
                'is_today' => $key === $today,
                'is_selected' => $key === $this->date,
                'crews' => $crews,
                'count' => array_sum(array_column($crews, 'count')),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Cancel the job behind a stop: mark it cancelled, pull it off the route, and
     * alert the crew's field staff (mirrors the Skip flow but for cancellations).
     */
    public function cancelStop(int $stopId): void
    {
        $stop = RouteStop::find($stopId);
        if (! $stop) {
            return;
        }

        if ($stop->job_id) {
            $job = \App\Models\Job::find($stop->job_id);
            $originalDate = $job?->scheduled_date?->toDateString();

            \App\Models\Job::where('id', $stop->job_id)->update([
                'status' => 'cancelled',
                'scheduled_date' => null,
            ]);

            if ($job) {
                $job->status = 'cancelled';
                app(\App\Services\JobNotifier::class)->notifySkippedOrCancelled($job, 'cancelled', $originalDate);
            }
        }

        $stop->delete();

        unset($this->stops, $this->unroutedJobs, $this->selectedStop, $this->selectedUnroutedJob, $this->summary, $this->unmappedStops, $this->listDays);
        $this->emitStopsUpdated();
    }

    /** Does a stop match the List-view search box (customer / property / service)? */
    private function stopMatchesSearch(RouteStop $stop, string $needle): bool
    {
        $parts = [];

        if ($c = $stop->customer) {
            $parts[] = $c->first_name;
            $parts[] = $c->last_name;
            $parts[] = $c->company_name;
        }
        if ($p = $stop->property) {
            $parts[] = $p->address;
            $parts[] = $p->city;
        }
        if ($stop->service?->name) {
            $parts[] = $stop->service->name;
        }
        foreach ($stop->job?->jobServices ?? [] as $js) {
            $parts[] = $js->service?->name;
        }

        $haystack = mb_strtolower(implode(' ', array_filter($parts)));

        return str_contains($haystack, $needle);
    }

    /** Distinct service groups attached to a stop (its own service + the job's services). */
    private function stopServiceGroups(RouteStop $stop): array
    {
        $groups = [];
        if ($stop->service?->service_group) {
            $groups[$stop->service->service_group] = true;
        }
        foreach ($stop->job?->jobServices ?? [] as $js) {
            if ($js->service?->service_group) {
                $groups[$js->service->service_group] = true;
            }
        }
        return array_keys($groups);
    }

    /**
     * Build a single clickable job card from one route stop.
     */
    private function buildStopCard(RouteStop $stop, array $crewMap): array
    {
        $c = $stop->customer;
        $customerName = $c
            ? (trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')) ?: ($c->company_name ?? '—'))
            : '—';

        $services = [];
        if ($stop->service?->name) {
            $services[$stop->service->name] = true;
        }
        foreach ($stop->job?->jobServices ?? [] as $js) {
            if ($js->service?->name) {
                $services[$js->service->name] = true;
            }
        }

        $crewId = (int) ($stop->route?->crew_id ?? 0);
        [$statusLabel, $statusKind] = $this->stopStatusBadge((string) $stop->status);

        return [
            'id' => (int) $stop->id,
            'job_id' => $stop->job_id ? (int) $stop->job_id : null,
            'crew_id' => $crewId,
            'sort_order' => (int) $stop->sort_order,
            'crew_name' => $crewMap[$crewId]['name'] ?? 'Unassigned',
            'color' => $crewMap[$crewId]['color'] ?? '#6b7280',
            'customer_name' => $customerName,
            'address' => $stop->property?->address ?? '—',
            'service_summary' => implode(' · ', array_slice(array_keys($services), 0, 2)),
            'status_label' => $statusLabel,
            'status_kind' => $statusKind,
            'do_not_move' => (bool) ($stop->job?->do_not_move ?? false),
        ];
    }

    /**
     * Map a raw route-stop status to a [label, css-kind] badge pair.
     *
     * @return array{0: string, 1: string}
     */
    private function stopStatusBadge(string $status): array
    {
        return match ($status) {
            'completed' => ['Complete', 'complete'],
            'in_progress' => ['In progress', 'in_progress'],
            'skipped' => ['Skipped', 'scheduled'],
            default => ['Scheduled', 'scheduled'],
        };
    }

    public function selectStop(int $id): void
    {
        $this->selectedStopId = $id;
        $this->selectedJobId = null;
        $this->selectedForemanId = null;
    }

    public function selectJob(int $id): void
    {
        $this->selectedJobId = $id;
        $this->selectedStopId = null;
        $this->selectedForemanId = null;
    }

    public function selectForeman(int $id): void
    {
        $this->selectedForemanId = $id;
        $this->selectedStopId = null;
        $this->selectedJobId = null;
        $this->chatBody = '';
        $this->chatPanelOpen = false;

        unset($this->chatMessages, $this->selectedForeman);
    }

    #[Computed]
    public function chatMessages(): array
    {
        if (! $this->selectedForemanId) {
            return [];
        }

        return ChatMessage::query()
            ->with('senderUser:id,name')
            ->where('employee_id', $this->selectedForemanId)
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => (int) $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender === ChatMessage::SENDER_OFFICE
                    ? ($message->senderUser?->name ?? 'Office')
                    : 'Foreman',
                'body' => $message->body,
                'attachment_type' => $message->attachment_type,
                'attachment_url' => $message->attachmentUrl(),
                'attachment_name' => $message->attachment_name,
                'time' => $message->created_at?->format('M j, g:i A'),
            ])
            ->all();
    }

    public function sendChat(): void
    {
        $body = trim($this->chatBody);
        if (! $this->selectedForemanId || $body === '') {
            return;
        }

        ChatMessage::create([
            'employee_id' => $this->selectedForemanId,
            'sender' => ChatMessage::SENDER_OFFICE,
            'sender_user_id' => auth()->id(),
            'body' => $body,
        ]);

        $this->chatBody = '';
        unset($this->chatMessages);

        $this->dispatch('dispatch:chat-updated');
    }

    public function openChatPanel(): void
    {
        if (! $this->selectedForemanId) {
            return;
        }
        $this->chatPanelOpen = true;

        // Opening the chat panel marks the foreman's unread messages as read.
        ChatMessage::query()
            ->where('employee_id', $this->selectedForemanId)
            ->where('sender', ChatMessage::SENDER_FOREMAN)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset($this->chatMessages, $this->foremanPins, $this->selectedForeman);
        $this->emitStopsUpdated();
        $this->dispatch('dispatch:chat-updated');
    }

    public function closeChatPanel(): void
    {
        $this->chatPanelOpen = false;
    }

    public function updatedChatAttachment(): void
    {
        if (! $this->selectedForemanId || ! $this->chatAttachment) {
            return;
        }

        $file = $this->chatAttachment;
        $mime = (string) $file->getMimeType();
        $type = str_starts_with($mime, 'video/')
            ? 'video'
            : (str_starts_with($mime, 'image/') ? 'photo' : 'file');

        $path = $file->store('chat-media', 'public');

        ChatMessage::create([
            'employee_id' => $this->selectedForemanId,
            'sender' => ChatMessage::SENDER_OFFICE,
            'sender_user_id' => auth()->id(),
            'attachment_type' => $type,
            'attachment_disk' => 'public',
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $mime,
            'attachment_size' => $file->getSize(),
        ]);

        $this->chatAttachment = null;
        unset($this->chatMessages);

        $this->dispatch('dispatch:chat-updated');
    }

    public function clearSelection(): void
    {
        $this->selectedStopId = null;
        $this->selectedJobId = null;
        $this->selectedForemanId = null;
        $this->chatPanelOpen = false;
    }

    /**
     * Tick/untick a crew in the Crews dropdown. This is the board's only crew
     * control now — the row of crew chips it replaced said the same thing twice
     * and cost a full row of vertical space.
     */
    public function toggleCrewVisibility(int $id): void
    {
        $hidden = array_map('intval', $this->hiddenCrewIds);

        if (in_array($id, $hidden, true)) {
            $this->hiddenCrewIds = array_values(array_filter($hidden, fn ($cid) => $cid !== $id));
        } else {
            $this->hiddenCrewIds = [...$hidden, $id];
        }

        // A drill-in from the Month view narrows ?crews= to one crew; once the
        // checkboxes are touched they take over as the filter.
        $this->crewIds = [];

        unset($this->activeCrewIds);
        $this->persistDispatchPrefs();
        $this->emitStopsUpdated();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['map', 'list', 'month'], true) ? $mode : 'map';
        $this->persistDispatchPrefs();

        $this->dispatch('dispatch:view-changed', mode: $this->viewMode);
        if ($this->viewMode === 'map') {
            // Re-feed the markers so the map can (re)build with current data.
            $this->emitStopsUpdated();
        }
    }

    /**
     * Single top-of-board view control: Map / Day / Week / Month. Day and Week
     * are both the list layout (they only differ in range), so "List" isn't shown
     * as its own choice — the Day/Week buttons select it implicitly.
     */
    public function setDispatchView(string $view): void
    {
        match ($view) {
            'day' => $this->applyView('list', 'day'),
            'week' => $this->applyView('list', 'week'),
            'month' => $this->applyView('month', null),
            default => $this->applyView('map', null),
        };
    }

    private function applyView(string $mode, ?string $range): void
    {
        if ($range !== null) {
            $this->listRange = $range === 'week' ? 'week' : 'day';
            unset($this->listDays, $this->listRangeLabel);
        }

        $this->setViewMode($mode);
    }

    /** Jump to a specific day and drop into the List view (used by the Month grid). */
    public function goToDay(string $date): void
    {
        $this->date = Carbon::parse($date)->toDateString();
        $this->viewMode = 'list';
        $this->selectedStopId = null;
        $this->persistDispatchPrefs();
        unset($this->listDays, $this->listRangeLabel);
    }

    /**
     * Jump from a Month-view crew row straight into that day's Map, filtered to
     * the chosen crew. Every other crew is unticked rather than the filter being
     * held somewhere invisible, so the Crews dropdown shows what happened — and
     * ticking them back is how you undo it.
     */
    public function goToDayCrewMap(string $date, int $crewId): void
    {
        $this->date = Carbon::parse($date)->toDateString();
        $this->viewMode = 'map';
        $this->crewIds = [];
        $this->hiddenCrewIds = array_values(array_filter(
            array_keys($this->crewColorMap()),
            fn (int $id): bool => $id !== $crewId,
        ));
        $this->selectedStopId = null;
        $this->persistDispatchPrefs();

        unset(
            $this->monthDays, $this->monthLabel, $this->listDays, $this->listRangeLabel,
            $this->stops, $this->summary, $this->foremanPins, $this->activeCrewIds,
        );
        $this->dispatch('dispatch:view-changed', mode: 'map');
        $this->emitStopsUpdated();
    }

    /** Tick every crew back on — the escape hatch from a one-crew drill-in. */
    public function showAllCrews(): void
    {
        $this->hiddenCrewIds = [];
        $this->crewIds = [];

        unset($this->activeCrewIds);
        $this->persistDispatchPrefs();
        $this->emitStopsUpdated();
    }

    /** Move the Month grid (and selected date) by whole months. */
    public function shiftMonth(int $months): void
    {
        $this->date = Carbon::parse($this->date)->addMonthsNoOverflow($months)->toDateString();
        unset($this->monthDays, $this->monthLabel, $this->listDays, $this->listRangeLabel);
        $this->emitStopsUpdated();
    }

    /**
     * Move the selected date by one unit of the CURRENT view's granularity —
     * a day (Map / List-Day), a week (List-Week), or a month (Month) — so the
     * single top-of-page date control drives every view (no per-view nav).
     */
    public function shiftPeriod(int $step): void
    {
        $date = Carbon::parse($this->date);

        if ($this->viewMode === 'month') {
            $date->addMonthsNoOverflow($step);
        } elseif ($this->viewMode === 'list' && $this->listRange === 'week') {
            $date->addWeeks($step);
        } else {
            $date->addDays($step);
        }

        $this->date = $date->toDateString();
        $this->selectedStopId = null;
        unset($this->monthDays, $this->monthLabel, $this->listDays, $this->listRangeLabel);
        $this->emitStopsUpdated();
    }

    /** Jump the calendar to a given month ('Y-m') from the month picker. */
    public function goToMonth(string $value): void
    {
        if ($value === '') {
            return;
        }
        $this->date = Carbon::parse($value . '-01')->toDateString();
        $this->selectedStopId = null;
        unset($this->monthDays, $this->monthLabel);
        $this->emitStopsUpdated();
    }

    /** Switch the List view between a single day and the full week (Mon–Sat). */
    public function setListRange(string $range): void
    {
        $this->listRange = $range === 'week' ? 'week' : 'day';
        unset($this->listDays, $this->listRangeLabel);
        $this->persistDispatchPrefs();
    }

    /** Persist crew visibility + view mode onto the authenticated user. */
    private function persistDispatchPrefs(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $user->forceFill([
            'dispatch_preferences' => [
                'hidden_crews' => array_values(array_map('intval', $this->hiddenCrewIds)),
                'view_mode' => $this->viewMode,
                'list_range' => $this->listRange,
            ],
        ])->save();
    }

    public function toggleServiceGroup(string $group): void
    {
        if (! array_key_exists($group, Service::GROUPS)) {
            return;
        }

        if (in_array($group, $this->serviceGroups, true)) {
            $this->serviceGroups = array_values(array_filter($this->serviceGroups, fn ($g) => $g !== $group));
        } else {
            $this->serviceGroups = [...$this->serviceGroups, $group];
        }

        unset($this->stops, $this->unroutedJobs, $this->selectedStop, $this->selectedUnroutedJob, $this->summary);
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

    public function toggleGps(): void
    {
        $this->showGps = ! $this->showGps;
        if (! $this->showGps && $this->selectedForemanId) {
            $this->selectedForemanId = null;
        }
        unset($this->foremanPins, $this->selectedForeman);
        $this->emitStopsUpdated();
    }

    /**
     * Move a stop's job to a different crew right from its card (issue #55).
     * The stop hops onto the target crew's route for the same day (created if
     * needed), and the job's crew_id follows so the two stay in sync.
     */
    public function reassignStopToCrew(int $stopId, int $crewId): void
    {
        $stop = RouteStop::with('route')->find($stopId);
        $crew = Crew::find($crewId);
        if (! $stop || ! $crew || ! $stop->route) {
            return;
        }

        // Already on this crew — nothing to do.
        if ((int) $stop->route->crew_id === $crewId) {
            return;
        }

        $date = $stop->route->route_date;

        DB::transaction(function () use ($stop, $crew, $date) {
            $target = Route::whereDate('route_date', $date)->where('crew_id', $crew->id)->first()
                ?? Route::create([
                    'name' => Carbon::parse($date)->format('D, M j') . ' — ' . $crew->name,
                    'route_date' => $date,
                    'crew_id' => $crew->id,
                    'status' => 'planning',
                ]);

            $stop->update([
                'route_id' => $target->id,
                'sort_order' => (int) $target->stops()->max('sort_order') + 1,
            ]);

            // Keep the underlying job's crew in step (drives Scheduling + reports).
            if ($stop->job_id) {
                Job::whereKey($stop->job_id)->update(['crew_id' => $crew->id]);
            }
        });

        unset($this->stops, $this->listDays, $this->selectedStop, $this->summary, $this->foremanPins);
        $this->emitStopsUpdated();
    }

    /**
     * Open the New Job modal, seeded with the day and crew currently in focus so
     * the common case (add a stop to what you're looking at) is one step.
     */
    public function openNewJobModal(): void
    {
        $defaultCrew = null;
        if ($this->selectedForemanId) {
            $defaultCrew = Crew::where('foreman_id', $this->selectedForemanId)->value('id');
        }
        $defaultCrew ??= ($this->activeCrewIds[0] ?? null) ?: array_key_first($this->crewColorMap());

        $this->newJobCustomerSearch = '';
        $this->newJob = [
            'customer_id' => null,
            'property_id' => null,
            'kind' => Job::KIND_SERVICE,
            'price' => null,
            'notes' => null,
            'priority' => 'normal',
            'scheduled_date' => $this->date,
            'crew_id' => $defaultCrew ? (int) $defaultCrew : null,
            'service_ids' => [],
        ];
        $this->showNewJobModal = true;
    }

    public function closeNewJobModal(): void
    {
        $this->showNewJobModal = false;
    }

    /**
     * Customer search results for the New Job modal.
     *
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function newJobCustomerResults(): array
    {
        $search = trim($this->newJobCustomerSearch);
        if (strlen($search) < 2) {
            return [];
        }

        return \App\Models\Customer::query()
            ->where(function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('last_name')
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'label' => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''))
                    ?: ($c->company_name ?: "Customer #{$c->id}"),
            ])
            ->all();
    }

    public function selectNewJobCustomer(int $customerId): void
    {
        $this->newJob['customer_id'] = $customerId;
        $this->newJob['property_id'] = null;
        $customer = \App\Models\Customer::find($customerId);
        $this->newJobCustomerSearch = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?? ''))
            : '';

        // Default to the customer's primary property so the form is usable in one pick.
        $this->newJob['property_id'] = \App\Models\Property::where('customer_id', $customerId)
            ->orderByDesc('is_primary')
            ->orderBy('address')
            ->value('id');
    }

    /**
     * Properties belonging to the chosen customer.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function newJobProperties(): array
    {
        if (! $this->newJob['customer_id']) {
            return [];
        }

        return \App\Models\Property::where('customer_id', $this->newJob['customer_id'])
            ->orderByDesc('is_primary')
            ->orderBy('address')
            ->get()
            ->mapWithKeys(fn ($p) => [(int) $p->id => trim($p->address . ($p->city ? ", {$p->city}" : ''))])
            ->all();
    }

    /**
     * Active services offered for the New Job service picker.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function newJobServiceOptions(): array
    {
        return Service::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Create the job from the modal via the shared creator, then drop it onto the
     * board (JobObserver + JobRouteAssigner place it on the crew's route).
     */
    public function createNewJob(): void
    {
        $data = $this->validate([
            'newJob.customer_id' => ['required', 'integer', 'exists:customers,id'],
            'newJob.property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'newJob.kind' => ['required', 'in:' . Job::KIND_SERVICE . ',' . Job::KIND_QUICK],
            'newJob.price' => ['nullable', 'numeric', 'min:0'],
            'newJob.notes' => ['nullable', 'string'],
            'newJob.priority' => ['nullable', 'in:low,normal,high,urgent'],
            'newJob.scheduled_date' => ['nullable', 'date'],
            'newJob.crew_id' => ['nullable', 'integer', 'exists:crews,id'],
            'newJob.service_ids' => ['array'],
            'newJob.service_ids.*' => ['integer', 'exists:services,id'],
        ])['newJob'];

        $isQuick = $data['kind'] === Job::KIND_QUICK;

        // Services come in as TBD lines; pricing is set later on the job.
        $serviceLines = $isQuick ? [] : array_map(
            fn ($id) => ['service_id' => (int) $id, 'pricing' => 'tbd'],
            $data['service_ids'],
        );

        app(\App\Services\JobFromFormCreator::class)->create([
            'customer_id' => $data['customer_id'],
            'property_id' => $data['property_id'],
            'kind' => $data['kind'],
            // A flat price and notes belong to a quick job only; a service job
            // totals up from its lines.
            'price' => $isQuick ? ($data['price'] ?: null) : null,
            'notes' => $isQuick ? ($data['notes'] ?: null) : null,
            'priority' => $data['priority'] ?: 'normal',
            'status' => $data['scheduled_date'] ? 'scheduled' : 'pending',
            'scheduled_date' => $data['scheduled_date'],
            'crew_id' => $data['crew_id'],
            'job_type' => 'one_time',
            'service_lines' => $serviceLines,
        ]);

        $this->showNewJobModal = false;
        unset($this->stops, $this->listDays, $this->summary, $this->foremanPins, $this->unroutedJobs);
        $this->emitStopsUpdated();
        $this->dispatch('dispatch:job-created');
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

        unset($this->stops, $this->selectedStop, $this->summary, $this->listDays);
        $this->emitStopsUpdated();
    }

    /** The job behind the current selection (a route stop or an unrouted job). */
    #[Computed]
    public function activeJob(): ?array
    {
        $jobId = $this->selectedJobId;

        if (! $jobId && $this->selectedStopId) {
            // Prefer the already-loaded map stops; fall back to the stop record so
            // the modal also works for List-view cards (whose stops aren't in the
            // map collection when the property lacks coordinates).
            foreach ($this->stops as $stop) {
                if ($stop['id'] === $this->selectedStopId) {
                    $jobId = $stop['job_id'];
                    break;
                }
            }
            $jobId ??= RouteStop::whereKey($this->selectedStopId)->value('job_id');
        }

        if (! $jobId) {
            return null;
        }

        $job = \App\Models\Job::query()
            ->with(['customer', 'property', 'jobServices.service'])
            ->find($jobId);

        if (! $job) {
            return null;
        }

        $customer = $job->customer;
        $customerName = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                ?: ($customer->company_name ?? '—'))
            : '—';

        // Foreman job timer (set via the mobile start/complete endpoints). Only
        // surfaced when the foreman has actually logged a start.
        $durationLabel = null;
        if ($job->started_at && $job->finished_at) {
            $minutes = $job->started_at->diffInMinutes($job->finished_at);
            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;
            $durationLabel = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
        }

        return [
            'id' => (int) $job->id,
            'title' => $job->title,
            'customer_name' => $customerName,
            'customer_phone' => $customer?->phone,
            'address' => $job->property?->address ?? '—',
            'city' => $job->property?->city,
            'notes' => $job->notes,
            'services' => $job->jobServices->map(fn ($jobService) => [
                'name' => $jobService->service?->name ?? 'Service',
                'description' => $jobService->description ?: $jobService->service?->description,
                'completed' => $jobService->completed_at !== null,
            ])->all(),
            'time_started' => $job->started_at?->format('M j, g:i A'),
            'time_finished' => $job->finished_at?->format('M j, g:i A'),
            'time_duration' => $durationLabel,
            'time_running' => $job->started_at !== null && $job->finished_at === null,
        ];
    }

    public function openServicesModal(): void
    {
        $this->showServicesModal = true;
    }

    /** Select a List-view job card's stop and open its details modal in one click. */
    public function openStopDetails(int $stopId): void
    {
        $this->selectStop($stopId);
        $this->showServicesModal = true;
    }

    public function closeServicesModal(): void
    {
        $this->showServicesModal = false;
    }

    /** Open the Skip-confirmation modal for the currently selected stop. */
    public function requestSkip(?int $id = null): void
    {
        $this->confirmSkipStopId = $id ?? $this->selectedStopId;
    }

    public function cancelSkip(): void
    {
        $this->confirmSkipStopId = null;
    }

    /**
     * Confirm skipping: mark the underlying job as 'skipped', clear its scheduled_date
     * (returning it to the unscheduled pile), and remove the stop from today's route.
     */
    public function confirmSkip(): void
    {
        if (! $this->confirmSkipStopId) {
            return;
        }

        $stop = RouteStop::find($this->confirmSkipStopId);
        if (! $stop) {
            $this->confirmSkipStopId = null;
            return;
        }

        if ($stop->job_id) {
            $job = \App\Models\Job::find($stop->job_id);
            $originalDate = $job?->scheduled_date?->toDateString();

            // Mass update bypasses model events (so the observer won't double-notify);
            // we alert the crew's foreman + spray techs explicitly with the pre-skip
            // date, since it's cleared here (issue #14).
            \App\Models\Job::where('id', $stop->job_id)->update([
                'status' => 'skipped',
                'scheduled_date' => null,
            ]);

            if ($job) {
                $job->status = 'skipped';
                app(\App\Services\JobNotifier::class)->notifySkippedOrCancelled($job, 'skipped', $originalDate);
            }
        }

        $stop->delete();

        $this->confirmSkipStopId = null;
        $this->selectedStopId = null;

        unset($this->stops, $this->unroutedJobs, $this->selectedStop, $this->selectedUnroutedJob, $this->summary, $this->unmappedStops, $this->listDays);
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
            foremen: $this->foremanPins,
            crewColors: $this->crewColorMap,
            serviceIcons: $this->serviceIconsEnabled,
        );
    }

    public function render()
    {
        return view('livewire.dispatch-board');
    }
}
