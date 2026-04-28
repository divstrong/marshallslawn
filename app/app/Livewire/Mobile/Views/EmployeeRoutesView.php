<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Crew;
use App\Models\Employee;
use App\Models\Route;
use App\Models\RouteStop;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeRoutesView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $filter = 'today';
    public ?int $viewingRouteId = null;

    public function mount(): void
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getCrewIdsProperty()
    {
        if (! $this->employee) {
            return collect();
        }

        return Crew::where('foreman_id', $this->employee->id)->pluck('id');
    }

    public function getRoutesProperty()
    {
        if ($this->crewIds->isEmpty()) {
            return collect();
        }

        $query = Route::whereIn('crew_id', $this->crewIds)
            ->withCount([
                'stops',
                'stops as completed_stops_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->with('crew')
            ->orderBy('route_date');

        if ($this->filter === 'today') {
            $query->whereDate('route_date', today());
        } elseif ($this->filter === 'upcoming') {
            $query->whereDate('route_date', '>=', today());
        } elseif ($this->filter === 'past') {
            $query->whereDate('route_date', '<', today())->orderByDesc('route_date');
        }

        return $query->get();
    }

    public function getViewingRouteProperty(): ?Route
    {
        if (! $this->viewingRouteId) {
            return null;
        }

        return Route::with([
            'crew',
            'stops.customer',
            'stops.property',
            'stops.service',
        ])->find($this->viewingRouteId);
    }

    public function viewRoute(int $id): void
    {
        $this->viewingRouteId = $id;
    }

    public function closeRoute(): void
    {
        $this->viewingRouteId = null;
    }

    public function updateStopStatus(int $stopId, string $status): void
    {
        $stop = RouteStop::find($stopId);
        if (! $stop || ! $this->canEditStop($stop)) {
            return;
        }

        $data = ['status' => $status];
        if ($status === 'completed') {
            $data['completed_at'] = now();
        } elseif ($status === 'pending' || $status === 'in_progress') {
            $data['completed_at'] = null;
        }

        $stop->update($data);

        // Auto-mark route status: if all stops done -> completed, else any in_progress -> active
        $route = $stop->route()->withCount([
            'stops',
            'stops as resolved' => fn ($q) => $q->whereIn('status', ['completed', 'skipped']),
            'stops as active' => fn ($q) => $q->where('status', 'in_progress'),
        ])->first();

        if ($route) {
            if ($route->stops_count > 0 && $route->resolved === $route->stops_count) {
                $route->update(['status' => 'completed']);
            } elseif ($route->active > 0 && $route->status !== 'active') {
                $route->update(['status' => 'active']);
            }
        }
    }

    protected function canEditStop(RouteStop $stop): bool
    {
        if (! $this->employee) {
            return false;
        }
        return $this->crewIds->contains($stop->route?->crew_id);
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-routes', [
            't' => $this->translations,
        ]);
    }
}
