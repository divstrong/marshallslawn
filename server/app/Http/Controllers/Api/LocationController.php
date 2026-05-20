<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives GPS breadcrumbs from the native app. The background location
 * task delivers batches, so a single request may carry several points.
 */
class LocationController extends Controller
{
    /**
     * POST /api/locations
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $data = $request->validate([
            'locations' => ['required', 'array', 'min:1', 'max:100'],
            'locations.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'locations.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'locations.*.accuracy' => ['nullable', 'numeric'],
            'locations.*.heading' => ['nullable', 'numeric'],
            'locations.*.speed' => ['nullable', 'numeric'],
            'locations.*.recorded_at' => ['nullable', 'date'],
        ]);

        $now = now();
        $rows = [];
        foreach ($data['locations'] as $point) {
            $rows[] = [
                'employee_id' => $employee->id,
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'accuracy' => $point['accuracy'] ?? null,
                'heading' => $point['heading'] ?? null,
                'speed' => $point['speed'] ?? null,
                'recorded_at' => isset($point['recorded_at'])
                    ? Carbon::parse($point['recorded_at'])
                    : $now,
                'created_at' => $now,
            ];
        }

        EmployeeLocation::insert($rows);

        return response()->json(['stored' => count($rows)]);
    }
}
