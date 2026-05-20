<?php

namespace App\Livewire;

use App\Models\Job;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RouteStopsManager extends Component
{
    public Route $route;

    public function mount(Route $route): void
    {
        $this->route = $route;
    }

    #[Computed]
    public function stops(): array
    {
        return $this->route
            ->stops()
            ->with([
                'job:id,title,priority',
                'customer:id,first_name,last_name,company_name,phone',
                'property:id,address,city,state,zip,latitude,longitude',
                'service:id,name,category',
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => $this->stopToArray($s))
            ->all();
    }

    #[Computed]
    public function unassignedJobs(): array
    {
        $assignedJobIds = RouteStop::query()
            ->whereHas('route', fn ($q) => $q->whereDate('route_date', $this->route->route_date))
            ->whereNotNull('job_id')
            ->pluck('job_id')
            ->all();

        return Job::query()
            ->with([
                'customer:id,first_name,last_name,company_name',
                'property:id,address,city,state,latitude,longitude',
                'recurringTemplate.service:id,name',
            ])
            ->whereDate('scheduled_date', $this->route->route_date)
            ->whereNotIn('id', $assignedJobIds)
            ->orderBy('id')
            ->get()
            ->map(fn ($j) => $this->jobToArray($j))
            ->all();
    }

    public function addJobToRoute(int $jobId, int $atIndex = -1): void
    {
        $job = Job::find($jobId);
        if (! $job) {
            return;
        }

        if (RouteStop::where('route_id', $this->route->id)->where('job_id', $job->id)->exists()) {
            return;
        }

        $total = $this->route->stops()->count();
        if ($atIndex < 0 || $atIndex > $total) {
            $atIndex = $total;
        }
        $position = $atIndex + 1;

        DB::transaction(function () use ($job, $position) {
            $this->route->stops()
                ->where('sort_order', '>=', $position)
                ->increment('sort_order');

            RouteStop::create([
                'route_id' => $this->route->id,
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'property_id' => $job->property_id,
                'sort_order' => $position,
                'status' => 'pending',
            ]);
        });

        unset($this->stops, $this->unassignedJobs);
    }

    public function removeStop(int $stopId): void
    {
        $stop = RouteStop::where('id', $stopId)
            ->where('route_id', $this->route->id)
            ->first();

        if (! $stop) {
            return;
        }

        DB::transaction(function () use ($stop) {
            $stop->delete();
            $this->compactSortOrder();
        });

        unset($this->stops, $this->unassignedJobs);
    }

    public function reorderStops(array $orderedIds): void
    {
        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds)));
        if (empty($orderedIds)) {
            return;
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $i => $id) {
                RouteStop::where('id', $id)
                    ->where('route_id', $this->route->id)
                    ->update(['sort_order' => $i + 1]);
            }
        });

        unset($this->stops);
    }

    private function compactSortOrder(): void
    {
        $stops = $this->route->stops()->orderBy('sort_order')->orderBy('id')->get();
        foreach ($stops as $i => $stop) {
            $stop->update(['sort_order' => $i + 1]);
        }
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

    public function render()
    {
        return view('livewire.route-stops-manager');
    }
}
