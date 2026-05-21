<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through Expo's push service. Failures are
 * logged but never thrown — a notification problem must not break the
 * action that triggered it.
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToEmployee(
        Employee $employee,
        string $title,
        string $body,
        array $data = [],
        string $channelId = 'default',
    ): void {
        $tokens = $employee->pushTokens()->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        $messages = array_map(fn (string $token): array => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => $channelId,
        ], $tokens);

        try {
            $response = Http::timeout(8)->acceptJson()->post(self::ENDPOINT, $messages);
            $this->pruneInvalidTokens($tokens, $response->json('data') ?? []);
        } catch (\Throwable $e) {
            Log::warning('Expo push notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Drop tokens Expo reports as no longer registered (app uninstalled).
     *
     * @param  array<int, string>  $tokens
     * @param  array<int, array<string, mixed>>  $receipts
     */
    private function pruneInvalidTokens(array $tokens, array $receipts): void
    {
        foreach ($receipts as $index => $receipt) {
            $isUnregistered = ($receipt['status'] ?? null) === 'error'
                && ($receipt['details']['error'] ?? null) === 'DeviceNotRegistered';

            if ($isUnregistered && isset($tokens[$index])) {
                PushToken::query()->where('token', $tokens[$index])->delete();
            }
        }
    }
}
