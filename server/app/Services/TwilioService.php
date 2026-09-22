<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Thin wrapper over the Twilio REST client. Every send is best-effort: if
 * credentials are absent or the API call fails, it logs and returns null rather
 * than throwing, so an opportunistic SMS never breaks the flow that triggered it.
 *
 * Every attempt also writes an SmsLog row — including the ones that never reach
 * Twilio — so a missing text is diagnosable from the admin panel instead of the
 * application log. The status webhook later walks that row to its final state.
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
     *
     * @param  string|null  $context  Template key or purpose, recorded on the log row.
     * @param  int|null  $customerId  Ties the log row to a customer when there is one.
     */
    public function sendSms(string $to, string $body, ?string $context = null, ?int $customerId = null): ?string
    {
        $raw = $to;
        $to = $this->normalizeNumber($to);

        if (! $to) {
            Log::warning('twilio.sms.skipped', ['reason' => 'invalid_number', 'context' => $context]);
            $this->record($customerId, $context, $raw, $body, SmsLog::STATUS_SKIPPED, 'invalid_number');

            return null;
        }

        if (! $this->client) {
            Log::info('twilio.sms.skipped', [
                'reason' => 'not_configured',
                'to' => $to,
                'context' => $context,
            ]);
            $this->record($customerId, $context, $to, $body, SmsLog::STATUS_SKIPPED, 'not_configured');

            return null;
        }

        $params = ['body' => $body];
        if (! empty($this->messagingServiceSid)) {
            $params['messagingServiceSid'] = $this->messagingServiceSid;
        } elseif (! empty($this->fromNumber)) {
            $params['from'] = $this->fromNumber;
        } else {
            Log::warning('twilio.sms.skipped', ['reason' => 'no_from_number', 'context' => $context]);
            $this->record($customerId, $context, $to, $body, SmsLog::STATUS_SKIPPED, 'no_from_number');

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

            $this->record(
                $customerId,
                $context,
                $to,
                $body,
                $message->status ?: SmsLog::STATUS_QUEUED,
                null,
                $message->sid,
            );

            return $message->sid;
        } catch (TwilioException $e) {
            Log::error('twilio.sms.failed', [
                'to' => $to,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            $this->record(
                $customerId,
                $context,
                $to,
                $body,
                SmsLog::STATUS_FAILED,
                'api_error',
                null,
                (string) $e->getCode(),
                $e->getMessage(),
            );

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
     * null for unusable input. Public so callers can validate a number before
     * offering to send to it.
     */
    public function normalizeNumber(?string $raw): ?string
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

    /**
     * Write the audit row. Never lets a logging failure break a send that already
     * succeeded — the message is out the door either way.
     */
    private function record(
        ?int $customerId,
        ?string $context,
        string $to,
        string $body,
        string $status,
        ?string $reason = null,
        ?string $sid = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        try {
            SmsLog::create([
                'customer_id' => $customerId,
                'template_key' => $context,
                'to_number' => mb_substr($to, 0, 32),
                'body' => $body,
                'message_sid' => $sid,
                'status' => $status,
                'reason' => $reason,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'delivered_at' => $status === SmsLog::STATUS_DELIVERED ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('twilio.sms.log_failed', ['error' => $e->getMessage(), 'context' => $context]);
        }
    }
}
