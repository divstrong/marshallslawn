<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Thin wrapper over the Twilio REST client. Every send is best-effort: if
 * credentials are absent or the API call fails, it logs and returns null rather
 * than throwing, so an opportunistic SMS never breaks the flow that triggered it.
 */
class TwilioService
{
    private ?Client $client = null;

    private ?string $fromNumber;

    private ?string $messagingServiceSid;

    public function __construct()
    {
        $sid = config('twilio.account_sid');
        $token = config('twilio.auth_token');
        $this->fromNumber = config('twilio.from_number');
        $this->messagingServiceSid = config('twilio.messaging_service_sid');

        if (! empty($sid) && ! empty($token)) {
            $this->client = new Client($sid, $token);
        }
    }

    /**
     * Send an SMS. Returns the message SID on success, or null when SMS is
     * disabled (no creds) or the send fails.
     */
    public function sendSms(string $to, string $body, ?string $context = null): ?string
    {
        $to = $this->normalizeNumber($to);

        if (! $to) {
            Log::warning('twilio.sms.skipped', ['reason' => 'invalid_number', 'context' => $context]);

            return null;
        }

        if (! $this->client) {
            Log::info('twilio.sms.skipped', [
                'reason' => 'not_configured',
                'to' => $to,
                'context' => $context,
            ]);

            return null;
        }

        $params = ['body' => $body];
        if (! empty($this->messagingServiceSid)) {
            $params['messagingServiceSid'] = $this->messagingServiceSid;
        } elseif (! empty($this->fromNumber)) {
            $params['from'] = $this->fromNumber;
        } else {
            Log::warning('twilio.sms.skipped', ['reason' => 'no_from_number', 'context' => $context]);

            return null;
        }

        try {
            $message = $this->client->messages->create($to, $params);

            Log::info('twilio.sms.sent', [
                'to' => $to,
                'context' => $context,
                'sid' => $message->sid,
                'status' => $message->status,
            ]);

            return $message->sid;
        } catch (TwilioException $e) {
            Log::error('twilio.sms.failed', [
                'to' => $to,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null
            && (! empty($this->fromNumber) || ! empty($this->messagingServiceSid));
    }

    /**
     * Best-effort normalization to E.164 (assumes US when no leading +). Returns
     * null for unusable input.
     */
    private function normalizeNumber(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $raw);
        if ($digits === '' || $digits === '+') {
            return null;
        }

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        // Strip any leading 1, then assume US +1.
        $digits = ltrim($digits, '1');
        if (strlen($digits) !== 10) {
            return null;
        }

        return '+1' . $digits;
    }
}
