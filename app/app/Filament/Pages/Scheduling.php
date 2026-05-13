<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Route;
use App\Models\RouteStop;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
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

                    Forms\Components\TextInput::make('title')
                        ->label('Job title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Mowing, Mulch install, Spring cleanup…'),

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
                            'title' => $data['title'],
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

    #[Computed]
    public function unassignedJobs(): array
    {
        $assignedJobIds = RouteStop::query()
            ->whereHas('route', fn ($q) => $q->whereDate('route_date', $this->date))
            ->whereNotNull('job_id')
            ->pluck('job_id')
            ->all();

        return Job::query()
            ->with([
                'customer:id,first_name,last_name,company_name',
                'property:id,address,city,state,latitude,longitude',
                'recurringTemplate.service:id,name',
            ])
            ->whereDate('scheduled_date', $this->date)
            ->whereNotIn('id', $assignedJobIds)
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
        unset($this->route, $this->routeStops, $this->unassignedJobs, $this->selectedCrew);
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
            'customer_name' => $customerName,
            'address' => $job->property?->address ?? '—',
            'city' => $job->property?->city,
            'service_name' => $job->recurringTemplate?->service?->name,
            'has_coords' => (bool) ($job->property?->latitude && $job->property?->longitude),
        ];
    }
}
