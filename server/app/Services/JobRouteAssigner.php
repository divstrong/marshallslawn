<?php

namespace App\Services;

use App\Models\Crew;
use App\Models\Job;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a job's route stop in step with its own crew + scheduled date.
 *
 * Once a dispatcher has picked both, the job's place on that crew's schedule is
 * already decided — it should not have to be dragged out of the Unassigned pile
 * first. This service makes `crew_id + scheduled_date` the source of truth and
 * mirrors it into the route/route_stops tables that the Scheduling and Dispatch
 * boards render from.
 *
 * Stops that a crew has already started or finished are never moved or deleted;
 * only 'pending' stops are re-homed.
 */
class JobRouteAssigner
{
    public function sync(Job $job): void
    {
        // A finished job's stop is dispatch history — never touch it.
        if ($job->status === 'completed') {
            return;
        }

        // Cancelled, or still missing a crew or a date: it has no place on a route.
        if ($job->status === 'cancelled' || ! $job->crew_id || ! $job->scheduled_date) {
            $this->detach($job);

            return;
        }

        $route = $this->routeFor((int) $job->crew_id, $job->scheduled_date);

        if (RouteStop::where('route_id', $route->id)->where('job_id', $job->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($job, $route) {
            // The job moved crew or date — pull it off the route it used to sit on.
            $this->detach($job, exceptRouteId: $route->id);

            RouteStop::create([
                'route_id' => $route->id,
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'property_id' => $job->property_id,
                'sort_order' => (int) $route->stops()->max('sort_order') + 1,
                'status' => 'pending',
            ]);
        });
    }

    /** Drop the job's not-yet-started stops, optionally sparing one route. */
    private function detach(Job $job, ?int $exceptRouteId = null): void
    {
        RouteStop::where('job_id', $job->id)
            ->where('status', 'pending')
            ->when($exceptRouteId, fn ($q) => $q->where('route_id', '!=', $exceptRouteId))
            ->delete();
    }

    private function routeFor(int $crewId, Carbon $date): Route
    {
        $route = Route::whereDate('route_date', $date)->where('crew_id', $crewId)->first();

        if ($route) {
            return $route;
        }

        $crewName = Crew::whereKey($crewId)->value('name') ?? "Crew {$crewId}";

        return Route::create([
            'name' => $date->format('D, M j') . ' — ' . $crewName,
            'route_date' => $date,
            'crew_id' => $crewId,
            'status' => 'planning',
        ]);
    }
}
