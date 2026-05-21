<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registration of Expo push tokens so the office can notify a foreman
 * when their app is closed.
 */
class PushTokenController extends Controller
{
    /**
     * POST /api/push-token — register (or refresh) this device's token.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:20'],
        ]);

        PushToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'employee_id' => $employee->id,
                'platform' => $data['platform'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json(['registered' => true]);
    }

    /**
     * DELETE /api/push-token — drop this device's token (on sign-out).
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        PushToken::query()
            ->where('token', $data['token'])
            ->where('employee_id', $employee->id)
            ->delete();

        return response()->json(['removed' => true]);
    }
}
