<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Route;
use App\Models\RouteStop;
use App\Services\GeocodingService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class Scheduling extends Page
{
    use ChecksResourceAccess;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.scheduling';

    #[Url(as: 'date')]
    public ?string $date = null;

    #[Url(as: 'crew')]
    public ?int $crewId = null;

    public function mount(): void
    {
        $this->date ??= now()->toDateString();
        if (! $this->crewId) {
            $this->crewId = Crew::orderBy('id')->value('id');
        }
    }

    protected function getHeaderActions(): array
    {
        $selectedCrewName = $this->selectedCrew['name'] ?? null;
        $hasCrew = $this->crewId !== null;

        return [
            Actions\Action::make('newJob')
                ->label('New Job')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Create a new job')
                ->modalSubmitActionLabel('Create job')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('property_id', null))
                        ->getSearchResultsUsing(fn (string $search) => Customer::query()
                            ->where(function ($q) use ($search) {
                                $q->where('last_name', 'LIKE', "%{$search}%")
                                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                                  ->orWhere('email', 'LIKE', "%{$search}%");
                            })
                            ->limit(25)
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => self::formatCustomerLabel($c)])
                            ->all())
                        ->getOptionLabelUsing(fn ($value) => $value
                            ? self::formatCustomerLabel(Customer::find($value))
                            : null),

                    Forms\Components\Select::make('property_id')
                        ->label('Property')
                        ->options(fn (Get $get) => $get('customer_id')
                            ? Property::query()
                                ->where('customer_id', $get('customer_id'))
                                ->orderByDesc('is_primary')
                                ->orderBy('address')
                                ->pluck('address', 'id')
                                ->all()
                            : [])
                        ->searchable()
                        ->required()
                        ->placeholder('Select a customer first'),

                    // This form has no service picker, so what it creates is a quick
                    // job by definition: a flat price and some notes. Build a full
                    // service scope from Jobs → New Job instead.
                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->placeholder('0.00')
                        ->helperText('Leave blank to quote it later.'),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('normal')
                        ->required(),

                    Forms\Components\Toggle::make('add_to_route')
                        ->label('Add to this crew\'s route immediately')
                        ->helperText($hasCrew
                            ? "Will land at the end of {$selectedCrewName}'s route for " . Carbon::parse($this->date)->format('D, M j')
                            : 'Pick a crew first to use this option — otherwise the job stays in the unassigned pool.')
                        ->default($hasCrew)
                        ->disabled(! $hasCrew)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $addToRoute = (bool) ($data['add_to_route'] ?? false) && $this->crewId !== null;

                    $job = DB::transaction(function () use ($data, $addToRoute) {
                        $job = Job::create([
                            'customer_id' => $data['customer_id'],
                            'property_id' => $data['property_id'],
                            'crew_id' => $addToRoute ? $this->crewId : null,
                            'kind' => Job::KIND_QUICK,
                            'price' => $data['price'] ?? null,
                            'description' => $data['description'] ?? null,
                            'priority' => $data['priority'] ?? 'normal',
                            'status' => 'scheduled',
                            'scheduled_date' => $this->date,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        if ($addToRoute) {
                            $this->addJobToRoute($job->id);
                        }

                        return $job;
                    });

                    // Make the job mappable straight away: if the chosen property has no
                    // coordinates on file yet, look them up now so it lands on Dispatch
                    // without a manual geocode step. Non-fatal if it fails.
                    $property = Property::find($data['property_id']);
                    if ($property && ($property->latitude === null || $property->longitude === null)) {
                        try {
                            app(GeocodingService::class)->geocodeProperty($property);
                        } catch (\Throwable $e) {
                            // Leave it for the Dispatch "Fix" button / geocode command.
                        }
                    }

                    Notification::make()
                        ->title('Job created')
                        ->body($addToRoute
                            ? "Added to {$this->selectedCrew['name']}'s route."
                            : 'Available in the unassigned pool.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private static function formatCustomerLabel(?Customer $c): ?string
    {
        if (! $c) {
            return null;
        }
        $name = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
        return $c->company_name
            ? "{$c->company_name} — {$name}"
            : ($name ?: ($c->email ?? '—'));
    }

    public function shiftDate(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
        $this->clearComputed();
    }

    public function selectCrew(int $id): void
    {
        $this->crewId = $id;
        $this->clearComputed();
    }

    #[Computed]
    public function crews(): array
    {
        return Crew::orderBy('id')->get(['id', 'name'])->map(fn ($c) => [
            'id' => (int) $c->id,
            'name' => $c->name,
        ])->all();
    }

    #[Computed]
    public function selectedCrew(): ?array
    {
        if (! $this->crewId) {
            return null;
        }
        foreach ($this->crews as $c) {
            if ($c['id'] === (int) $this->crewId) {
                return $c;
            }
        }
        return null;
    }

    #[Computed]
    public function route(): ?Route
    {
        if (! $this->crewId) {
            return null;
        }
        return Route::query()
            ->whereDate('route_date', $this->date)
            ->where('crew_id', $this->crewId)
            ->first();
    }

    #[Computed]
    public function routeStops(): array
    {
        $route = $this->route;
        if (! $route) {
            return [];
        }

        return RouteStop::query()
            ->with([
                'job:id,title,priority',
                'customer:id,first_name,last_name,company_name,phone',
                'property:id,address,city,state,zip,latitude,longitude',
                'service:id,name,category',
            ])
            ->where('route_id', $route->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => $this->stopToArray($s))
            ->all();
    }

    /**
     * The "Unassigned" pile: work that still needs a dispatcher's decision.
     *
     * A job with both a scheduled date and a crew is already on that crew's route
     * (JobRouteAssigner keeps the route stop in sync), so it never lands here. What
     * remains is genuinely undecided work:
     *   - jobs with no scheduled date at all (shown on every day),
     *   - jobs scheduled for the selected day that have no crew yet,
     *   - skipped jobs that have fallen off every route.
     *
     * Uses correlated NOT EXISTS subqueries (whereDoesntHave) rather than plucking
     * every route-stop id into PHP and building a giant whereNotIn — cheaper per
     * render and it scales as the route_stops table grows.
     */
    private function unassignedJobsQuery(): Builder
    {
        return Job::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDoesntHave('routeStops', fn ($r) => $r
                ->whereHas('route', fn ($rr) => $rr->whereDate('route_date', $this->date)))
            ->where(function ($q) {
                // Undated work — needs a date before it can be routed.
                $q->whereNull('scheduled_date')
                // …dated for the day on screen but with nobody assigned to it.
                ->orWhere(function ($qq) {
                    $qq->whereDate('scheduled_date', $this->date)
                       ->whereNull('crew_id');
                })
                // …plus any skipped job not currently on any route (even a future day).
                ->orWhere(function ($qq) {
                    $qq->where('status', 'skipped')
                       ->whereDoesntHave('routeStops');
                });
            });
    }

    #[Computed]
    public function unassignedCount(): int
    {
        return $this->unassignedJobsQuery()->count();
    }

    #[Computed]
    public function activeJobs(): array
    {
        return $this->unassignedJobsQuery()
            ->with([
                'customer:id,first_name,last_name,company_name',
                'property:id,address,city,state,latitude,longitude',
                'recurringTemplate.service:id,name',
            ])
            // Skipped jobs surface first so they're easy to grab and re-assign.
            ->orderByRaw("CASE WHEN status = 'skipped' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get()
            ->map(fn ($j) => $this->jobToArray($j))
            ->all();
    }

    public function addJobToRoute(int $jobId, int $atIndex = -1): void
    {
        $route = $this->getOrCreateRoute();
        if (! $route) {
            return;
        }

        $job = Job::find($jobId);
        if (! $job) {
            return;
        }

        // Don't double-add
        if (RouteStop::where('route_id', $route->id)->where('job_id', $job->id)->exists()) {
            return;
        }

        $total = $route->stops()->count();
        if ($atIndex < 0 || $atIndex > $total) {
            $atIndex = $total;
        }
        $position = $atIndex + 1;

        DB::transaction(function () use ($route, $job, $position) {
            $route->stops()
                ->where('sort_order', '>=', $position)
                ->increment('sort_order');

            RouteStop::create([
                'route_id' => $route->id,
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'property_id' => $job->property_id,
                'sort_order' => $position,
                'status' => 'pending',
            ]);

            // If this job was skipped (or has no scheduled date), assigning it to a route
            // implicitly re-schedules it for that route's date and hands it to that crew.
            $updates = [];
            if ($job->status === 'skipped') {
                $updates['status'] = 'scheduled';
            }
            if ($job->scheduled_date?->toDateString() !== $route->route_date->toDateString()) {
                $updates['scheduled_date'] = $route->route_date;
            }
            if ((int) $job->crew_id !== (int) $route->crew_id) {
                $updates['crew_id'] = $route->crew_id;
            }
            if (! empty($updates)) {
                $job->update($updates);
            }
        });

        $this->clearComputed();
    }

    public function removeStop(int $stopId): void
    {
        $route = $this->route;
        if (! $route) {
            return;
        }

        $stop = RouteStop::where('id', $stopId)
            ->where('route_id', $route->id)
            ->first();

        if (! $stop) {
            return;
        }

        DB::transaction(function () use ($stop, $route) {
            $stop->delete();
            $this->compactSortOrder($route);
        });

        $this->clearComputed();
    }

    public function reorderStops(array $orderedIds): void
    {
        $route = $this->route;
        if (! $route) {
            return;
        }

        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds)));
        if (empty($orderedIds)) {
            return;
        }

        DB::transaction(function () use ($orderedIds, $route) {
            foreach ($orderedIds as $i => $id) {
                RouteStop::where('id', $id)
                    ->where('route_id', $route->id)
                    ->update(['sort_order' => $i + 1]);
            }
        });

        $this->clearComputed();
    }

    private function getOrCreateRoute(): ?Route
    {
        if (! $this->crewId) {
            return null;
        }

        $existing = $this->route;
        if ($existing) {
            return $existing;
        }

        $crewName = $this->selectedCrew['name'] ?? 'Crew ' . $this->crewId;
        $name = Carbon::parse($this->date)->format('D, M j') . ' — ' . $crewName;

        $route = Route::create([
            'name' => $name,
            'route_date' => $this->date,
            'crew_id' => $this->crewId,
            'status' => 'planning',
        ]);

        unset($this->route);
        return $route;
    }

    private function compactSortOrder(Route $route): void
    {
        $stops = $route->stops()->orderBy('sort_order')->orderBy('id')->get();
        foreach ($stops as $i => $stop) {
            $stop->update(['sort_order' => $i + 1]);
        }
    }

    private function clearComputed(): void
    {
        unset($this->route, $this->routeStops, $this->activeJobs, $this->unassignedCount, $this->selectedCrew);
    }

    private function stopToArray(RouteStop $stop): array
    {
        $customer = $stop->customer;
        $customerName = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                ?: ($customer->company_name ?? '—'))
            : '—';

        return [
            'id' => (int) $stop->id,
            'sort_order' => (int) $stop->sort_order,
            'status' => $stop->status,
            'job_id' => $stop->job_id ? (int) $stop->job_id : null,
            'title' => $stop->job?->title,
            'customer_name' => $customerName,
            'customer_phone' => $customer?->phone,
            'address' => $stop->property?->address ?? '—',
            'city' => $stop->property?->city,
            'service_name' => $stop->service?->name,
            'has_coords' => (bool) ($stop->property?->latitude && $stop->property?->longitude),
            'notes' => $stop->notes,
        ];
    }

    private function jobToArray(Job $job): array
    {
        $customer = $job->customer;
        $customerName = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                ?: ($customer->company_name ?? '—'))
            : '—';

        return [
            'id' => (int) $job->id,
            'title' => $job->title,
            'priority' => $job->priority,
            'status' => $job->status,
            'is_skipped' => $job->status === 'skipped',
            'scheduled_date' => $job->scheduled_date?->toDateString(),
            'customer_name' => $customerName,
            'address' => $job->property?->address ?? '—',
            'city' => $job->property?->city,
            'service_name' => $job->recurringTemplate?->service?->name,
            'has_coords' => (bool) ($job->property?->latitude && $job->property?->longitude),
        ];
    }
}
