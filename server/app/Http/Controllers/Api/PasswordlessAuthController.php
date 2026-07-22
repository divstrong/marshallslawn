<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Mail\LoginCodeMail;
use App\Models\Employee;
use App\Models\EmployeeLoginCode;
use App\Services\AppReviewAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Passwordless sign-in for the native app. An employee enters their email,
 * receives a six-digit code, and types it back to get a Sanctum token. There
 * is no password anywhere in this flow.
 *
 * Tokens do not expire (`sanctum.expiration` is null), so a crew member stays
 * signed in until they sign out or the office revokes the token.
 */
class PasswordlessAuthController extends Controller
{
    private const CODE_TTL_MINUTES = 10;

    /** Wrong guesses allowed against a single code before it is burned. */
    private const MAX_ATTEMPTS_PER_CODE = 5;

    /** Codes a single email may request per hour. */
    private const MAX_CODES_PER_HOUR = 5;

    public function __construct(private readonly AppReviewAccount $review) {}

    /**
     * Email a fresh sign-in code to an active employee.
     */
    public function requestCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));

        // App Store reviewers can't read the mailbox a code would go to. The
        // demo account walks the same two-step flow, but its code is fixed and
        // nothing is emailed — so answer as though a code went out. Handled
        // before the lookup because the record is provisioned on first verify.
        if ($this->review->matchesEmail($email)) {
            Log::info('auth.app_review_code_requested', ['ip' => $request->ip()]);

            return response()->json([
                'message' => 'Code sent.',
                'expires_in_minutes' => self::CODE_TTL_MINUTES,
            ]);
        }

        $employee = Employee::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        // This is a staff-only app with no self sign-up, so a precise message
        // is worth more than hiding which addresses belong to employees — the
        // code itself is what actually gates access.
        if (! $employee) {
            throw ValidationException::withMessages([
                'email' => ['No account found for that email. Check with the office.'],
            ]);
        }

        if ($employee->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is no longer active.'],
            ]);
        }

        // Throttle per address so a known employee email can't be used to spam
        // someone's inbox (or our Postmark quota).
        if (! RateLimiter::attempt('login-code:'.$email, self::MAX_CODES_PER_HOUR, fn () => null, decaySeconds: 3600)) {
            throw ValidationException::withMessages([
                'email' => ['Too many code requests. Please try again later.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);

        // Burn any earlier codes so only the newest one works — otherwise a
        // resend would leave the previous code live until it expired.
        EmployeeLoginCode::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]);

        EmployeeLoginCode::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        Mail::to($employee->email)->send(new LoginCodeMail($code, self::CODE_TTL_MINUTES));

        return response()->json([
            'message' => 'Code sent.',
            'expires_in_minutes' => self::CODE_TTL_MINUTES,
        ]);
    }

    /**
     * Exchange a valid code for a Sanctum token.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string'],
        ]);

        $email = strtolower(trim($data['email']));

        // Review account: fixed code, and the employee record is created the
        // first time it is used so a submission needs no manual setup.
        if ($this->review->matchesEmail($email)) {
            if (! $this->review->matchesCode($data['code'])) {
                throw ValidationException::withMessages([
                    'code' => ['Incorrect code.'],
                ]);
            }

            $employee = $this->review->provision();

            Log::info('auth.app_review_signed_in', [
                'employee_id' => $employee->id,
                'ip' => $request->ip(),
            ]);

            return $this->tokenResponse($employee);
        }

        $row = EmployeeLoginCode::activeFor($email)->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'code' => ['That code has expired. Request a new one.'],
            ]);
        }

        if ($row->attempts >= self::MAX_ATTEMPTS_PER_CODE) {
            $row->update(['consumed_at' => now()]);

            throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Request a new code.'],
            ]);
        }

        if (! Hash::check($data['code'], $row->code_hash)) {
            $row->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['Incorrect code.'],
            ]);
        }

        // Valid — burn it before issuing anything, so the same code can never
        // be redeemed twice.
        $row->update(['consumed_at' => now()]);

        $employee = Employee::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        // Re-checked because the account may have been deactivated in the
        // minutes between requesting the code and entering it.
        if (! $employee || $employee->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is no longer active.'],
            ]);
        }

        return $this->tokenResponse($employee);
    }

    private function tokenResponse(Employee $employee): JsonResponse
    {
        // One device at a time, matching how the app has always behaved.
        $employee->tokens()->delete();
        $token = $employee->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'employee' => new EmployeeResource($employee),
        ]);
    }
}
