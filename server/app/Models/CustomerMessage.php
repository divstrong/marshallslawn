<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One message in a customer <-> office conversation. The thread is keyed by
 * `customer_id`; `sender` is 'customer' or 'office'.
 */
class CustomerMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';
    public const SENDER_OFFICE = 'office';

    protected $fillable = [
        'customer_id',
        'sender',
        'sender_user_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // A customer -> office message raises the office bell (admins + managers),
        // mirroring the foreman chat alert.
        static::created(function (CustomerMessage $message): void {
            if ($message->sender !== self::SENDER_CUSTOMER) {
                return;
            }

            $message->notifyOfficeOfIncomingChat();
        });
    }

    protected function notifyOfficeOfIncomingChat(): void
    {
        $customer = $this->customer;
        $name = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: ($customer->company_name ?? 'A customer'))
            : 'A customer';

        $recipients = User::whereHas('role', function ($q) {
            $q->where('is_admin', true)->orWhere('name', 'manager');
        })->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = \Filament\Notifications\Notification::make()
            ->title('New message from ' . $name)
            ->body(\Illuminate\Support\Str::limit($this->body, 120))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->actions([
                \Filament\Actions\Action::make('open')
                    ->label('Open customer')
                    ->url(route('filament.admin.resources.customers.edit', $this->customer_id))
                    ->markAsRead(),
            ]);

        foreach ($recipients as $recipient) {
            $notification->sendToDatabase($recipient);
        }
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
