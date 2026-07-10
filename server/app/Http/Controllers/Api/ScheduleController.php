<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\Employee;
use App\Models\Job;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Foreman's day view: jobs for a given date, ordered by the crew's
 * route. Jobs scheduled that day with no route stop are appended after.
 */
class ScheduleController extends Controller
{
    /**
     * GET /api/schedule?date=YYYY-MM-DD  (defaults to today)
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $date = $request->date('date') ?? today();
        $crewIds = $employee->crewIds();

        // Jobs in route order: route stops for the crew(s) on this date.
        $routeJobIds = [];
        $routes = Route::query()
            ->whereIn('crew_id', $crewIds)
            ->whereDate('route_date', $date)
            ->with(['stops' => fn ($q) => $q->whereNotNull('job_id')])
            ->orderBy('id')
            ->get();

        foreach ($routes as $route) {
            foreach ($route->stops as $stop) {
                $routeJobIds[] = $stop->job_id;
            }
        }

        // Jobs scheduled that day but not covered by a route stop.
        $scheduledJobIds = Job::query()
            ->whereIn('crew_id', $crewIds)
            ->whereDate('scheduled_date', $date)
            ->pluck('id')
            ->all();

        $orderedIds = array_values(array_unique(array_merge($routeJobIds, $scheduledJobIds)));

        $jobs = Job::query()
            ->with(['property', 'customer', 'crew'])
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        $ordered = collect($orderedIds)
            ->map(fn ($id) => $jobs->get($id))
            ->filter()
            ->values();

        foreach ($ordered as $index => $job) {
            $job->route_order = $index + 1;
        }

        // Batch-translate the day's jobs once for non-English staff (issue #56).
        \App\Support\TranslationWarmer::jobs($ordered, \App\Support\AppLocale::target($request));

        return response()->json([
            'date' => $date->toDateString(),
            'is_today' => $date->isToday(),
            'job_count' => $ordered->count(),
            'jobs' => JobResource::collection($ordered),
        ]);
    }
}
