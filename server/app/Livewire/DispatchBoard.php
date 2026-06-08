<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\Crew;
use App\Models\RouteStop;
use App\Models\Service;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

    /** Right-side chat panel state. */
    public bool $chatPanelOpen = false;

    #[Url(as: 'gps')]
    public bool $showGps = true;

    /** Composer state for the foreman chat panel. */
    public string $chatBody = '';

    public $chatAttachment = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->hasAccessTo('Dispatch'), 403);

        $this->date ??= now()->toDateString();
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
                if (! empty($this->crewIds)) {
                    $q->whereIn('crew_id', $this->crewIds);
                }
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
            ->whereIn('id', array_keys($crewMap))
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

            if (! empty($this->crewIds) && ! in_array((int) $crew->id, array_map('intval', $this->crewIds), true)) {
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

        unset($this->stops, $this->unroutedJobs, $this->selectedStop, $this->selectedUnroutedJob, $this->summary, $this->unmappedStops);
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
