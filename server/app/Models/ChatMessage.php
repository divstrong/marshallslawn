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
        static::created(function (ChatMessage $message): void {
            $preview = $message->body ?: match ($message->attachment_type) {
                'video' => 'Sent a video',
                'file' => 'Sent a file',
                default => 'Sent a photo',
            };

            // Office -> foreman: push the message to the foreman's app.
            if ($message->sender === self::SENDER_OFFICE) {
                if ($employee = $message->employee) {
                    app(\App\Services\ExpoPushService::class)->sendToEmployee(
                        $employee,
                        'Message from the office',
                        $preview,
                        ['type' => 'chat'],
                        'chat',
                    );
                }

                return;
            }

            // Foreman -> office: raise a bell notification for the admins so they
            // see the incoming chat from the app.
            if ($message->sender === self::SENDER_FOREMAN) {
                $message->notifyOfficeOfIncomingChat($preview);
            }
        });
    }

    /**
     * Send a Filament database notification (the top-bar bell) to the office —
     * admins and managers — for a new incoming message from a foreman.
     */
    protected function notifyOfficeOfIncomingChat(string $preview): void
    {
        $employee = $this->employee;
        $name = $employee
            ? (trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: ($employee->name ?? 'A crew member'))
            : 'A crew member';

        // Office staff = admins + managers.
        $recipients = User::whereHas('role', function ($q) {
            $q->where('is_admin', true)->orWhere('name', 'manager');
        })->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = \Filament\Notifications\Notification::make()
            ->title('New message from ' . $name)
            ->body(\Illuminate\Support\Str::limit($preview, 120))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->actions([
                \Filament\Actions\Action::make('open')
                    ->label('Open chat')
                    ->url(route('filament.admin.pages.messages'))
                    ->markAsRead(),
            ]);

        foreach ($recipients as $recipient) {
            $notification->sendToDatabase($recipient);
        }
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
