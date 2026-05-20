<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeLogResource;
use App\Models\Employee;
use App\Models\TimeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Day-level shift tracking. One open TimeLog row per employee is the
 * active shift; clock-out closes it. Distinct from the foreman job clock.
 */
class TimeLogController extends Controller
{
    /**
     * GET /api/time-logs — active shift, today's shifts, recent history.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $active = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        $today = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('clock_in', today())
            ->orderByDesc('clock_in')
            ->get();

        $history = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('clock_in', '<', today())
            ->orderByDesc('clock_in')
            ->limit(20)
            ->get();

        return response()->json([
            'active_shift' => $active ? new TimeLogResource($active) : null,
            'minutes_today' => $today->sum(fn (TimeLog $log) => $this->minutes($log)),
            'today' => TimeLogResource::collection($today),
            'history' => TimeLogResource::collection($history),
        ]);
    }

    /**
     * POST /api/time-logs/clock-in
     */
    public function clockIn(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $hasOpenShift = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->exists();

        if ($hasOpenShift) {
            return response()->json(['message' => 'You already have an open shift.'], 422);
        }

        $log = TimeLog::create([
            'employee_id' => $employee->id,
            'clock_in' => now(),
            'break_minutes' => 0,
            'status' => 'active',
        ]);

        return response()->json(['shift' => new TimeLogResource($log)]);
    }

    /**
     * POST /api/time-logs/clock-out
     */
    public function clockOut(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $shift = TimeLog::query()
            ->where('employee_id', $employee->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        if ($shift === null) {
            return response()->json(['message' => 'No open shift to clock out of.'], 422);
        }

        $shift->update(['clock_out' => now(), 'status' => 'completed']);

        return response()->json(['shift' => new TimeLogResource($shift)]);
    }

    private function minutes(TimeLog $log): int
    {
        if (! $log->clock_in) {
            return 0;
        }

        $end = $log->clock_out ?? now();

        return (int) max(0, $log->clock_in->diffInMinutes($end) - (int) $log->break_minutes);
    }
}
