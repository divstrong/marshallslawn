<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session endpoints for the native app. Employees authenticate directly (the
 * users table is reserved for the Filament back-office).
 *
 * Sign-in itself is passwordless and lives in {@see PasswordlessAuthController};
 * what remains here is the dev shortcut plus session read/teardown.
 */
class AuthController extends Controller
{
    /**
     * Local-only shortcut so the in-app dev role switcher can jump
     * straight into a Foreman / Field / Estimator experience.
     */
    public function devLogin(Request $request): JsonResponse
    {
        abort_unless(app()->environment('local') || config('app.debug') || config('app.dev_login'), 403, 'Dev login is disabled.');

        $data = $request->validate([
            'role' => ['required', 'in:foreman,spray_tech,field,estimator'],
        ]);

        $employee = Employee::query()
            ->where('role', $data['role'])
            ->where('status', 'active')
            ->whereNotNull('email')
            ->orderByRaw("email LIKE '%@marshallslawn.test' DESC")
            ->orderBy('id')
            ->first();

        abort_if($employee === null, 404, "No active {$data['role']} employee exists. Run: php artisan db:seed --class=MobileDemoSeeder");

        return $this->tokenResponse($employee);
    }

    /**
     * The authenticated employee, with role + capability flags.
     */
    public function me(Request $request): EmployeeResource
    {
        return new EmployeeResource($request->user());
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function tokenResponse(Employee $employee): JsonResponse
    {
        // One device at a time keeps things simple for now.
        $employee->tokens()->delete();
        $token = $employee->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'employee' => new EmployeeResource($employee),
        ]);
    }
}
