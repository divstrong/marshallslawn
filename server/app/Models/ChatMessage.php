<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One message in a foreman <-> office conversation. The thread is keyed
 * by `employee_id` (the foreman); `sender` is 'foreman' or 'office'.
 */
class ChatMessage extends Model
{
    use HasFactory;

    public const SENDER_FOREMAN = 'foreman';
    public const SENDER_OFFICE = 'office';

    protected $fillable = [
        'employee_id',
        'sender',
        'sender_user_id',
        'body',
        'attachment_type',
        'attachment_disk',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Notify the foreman whenever the office sends them a message.
        static::created(function (ChatMessage $message): void {
            if ($message->sender !== self::SENDER_OFFICE) {
                return;
            }

            $employee = $message->employee;
            if (! $employee) {
                return;
            }

            $preview = $message->body
                ?: ($message->attachment_type === 'video' ? 'Sent a video' : 'Sent a photo');

            app(\App\Services\ExpoPushService::class)->sendToEmployee(
                $employee,
                'Message from the office',
                $preview,
                ['type' => 'chat'],
                'chat',
            );
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        return url('storage/' . $this->attachment_path);
    }
}
