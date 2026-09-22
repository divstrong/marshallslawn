<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An outbound SMS attempt. Written by TwilioService for every send — delivered,
 * failed, or never attempted — so the office can answer "did the customer get
 * their text?" without reading application logs.
 */
class SmsLog extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNDELIVERED = 'undelivered';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'customer_id',
        'template_key',
        'to_number',
        'body',
        'message_sid',
        'status',
        'reason',
        'error_code',
        'error_message',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Statuses that mean the message did not and will not arrive. */
    public function isFailure(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_UNDELIVERED, self::STATUS_SKIPPED], true);
    }

    /**
     * Twilio's status strings map 1:1 onto ours except for the ones we invented
     * (skipped). Anything unrecognized is stored verbatim rather than dropped.
     */
    public function applyTwilioStatus(string $status, ?string $errorCode = null): void
    {
        $this->status = $status !== '' ? $status : $this->status;
        $this->error_code = $errorCode ?: $this->error_code;

        if ($status === self::STATUS_DELIVERED && ! $this->delivered_at) {
            $this->delivered_at = now();
        }

        $this->save();
    }
}
