<?php

namespace App\Filament\Resources\SmsLogResource\Pages;

use App\Filament\Resources\SmsLogResource;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\SmsTemplate;
use App\Services\TwilioService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSmsLogs extends ListRecords
{
    protected static string $resource = SmsLogResource::class;

    protected static ?string $title = 'SMS Messages';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendSms')
                ->label('Send SMS')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading('Send a text message')
                ->modalSubmitActionLabel('Send')
                ->disabled(fn (): bool => ! $this->canSend())
                ->tooltip(fn (): ?string => $this->canSend() ? null : $this->blockedReason())
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Mobile number')
                        ->tel()
                        ->placeholder('(804) 555-1234')
                        ->helperText('10-digit US number — the +1 is added automatically.')
                        ->required()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, callable $fail) {
                                    if (strlen(preg_replace('/\D/', '', (string) $value)) !== 10) {
                                        $fail('Enter a 10-digit US mobile number.');
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Textarea::make('body')
                        ->label('Message')
                        ->rows(4)
                        ->required()
                        ->maxLength(1000)
                        ->live(onBlur: true)
                        ->helperText(function (?string $state): string {
                            if (blank($state)) {
                                return 'Carriers bill per 160-character segment.';
                            }

                            $info = SmsTemplate::segmentInfo($state);

                            return $info['chars'] . ' characters · ' . $info['segments'] . ' '
                                . str('segment')->plural($info['segments']) . ' · ' . $info['encoding'];
                        }),
                ])
                ->action(fn (array $data) => $this->send($data)),
        ];
    }

    /** Credentials present and the channel armed — the same gates a real send passes. */
    private function canSend(): bool
    {
        return app(TwilioService::class)->isConfigured()
            && (bool) config('twilio.notifications.enabled');
    }

    private function blockedReason(): string
    {
        return app(TwilioService::class)->isConfigured()
            ? 'Text messaging is turned off (TWILIO_NOTIFICATIONS_ENABLED).'
            : 'Twilio is not configured on this server.';
    }

    /**
     * Send an ad-hoc message typed by the office. When the number belongs to a
     * known customer the send is tied to their record and mirrored into their
     * thread; an unknown number still sends and is still logged, just unattached.
     */
    private function send(array $data): void
    {
        $twilio = app(TwilioService::class);
        $to = $twilio->normalizeNumber($data['phone']);

        if (! $to) {
            Notification::make()
                ->title('Could not send')
                ->body('That number is not a usable US mobile number.')
                ->danger()
                ->send();

            return;
        }

        $customer = Customer::findByPhone($to);

        // A STOP is a carrier-level revocation, not a preference. It outranks
        // anything the office types here.
        if ($customer && $customer->sms_consent_status === Customer::SMS_OPTED_OUT) {
            Notification::make()
                ->title('Blocked — customer opted out')
                ->body(trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                    . ' replied STOP, so no message can be sent to this number.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $sid = $twilio->sendSms($to, $data['body'], 'manual', $customer?->id);

        if (! $sid) {
            Notification::make()
                ->title('Send failed')
                ->body('Twilio rejected the message. The reason is on the newest row below.')
                ->danger()
                ->send();

            return;
        }

        if ($customer) {
            CustomerMessage::create([
                'customer_id' => $customer->id,
                'sender' => CustomerMessage::SENDER_OFFICE,
                'sender_user_id' => auth()->id(),
                'body' => $data['body'],
            ]);
        }

        Notification::make()
            ->title('Message sent')
            ->body('Sent to ' . $to . ($customer ? ' (' . trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) . ')' : ''))
            ->success()
            ->send();
    }
}
