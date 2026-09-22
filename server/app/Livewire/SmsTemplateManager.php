<?php

namespace App\Livewire;

use App\Models\SmsTemplate;
use App\Services\TwilioService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Settings → Notifications: edit each customer SMS body (with {placeholders}),
 * toggle whether that event actually sends, see it rendered with sample data,
 * and fire a one-off test to a staff phone. Copy and activation are controlled
 * here so the office never needs a deploy to change a message.
 */
class SmsTemplateManager extends Component
{
    /** Working copy keyed by template id: ['body' => ..., 'is_active' => ...]. */
    public array $rows = [];

    /** Test recipient per template id, as typed. */
    public array $testNumbers = [];

    /** Per-template result of the last test send: ['ok' => bool, 'message' => string]. */
    public array $testResults = [];

    public function mount(): void
    {
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = SmsTemplate::orderBy('id')->get()
            ->mapWithKeys(fn (SmsTemplate $t) => [
                $t->id => ['body' => $t->body, 'is_active' => (bool) $t->is_active],
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SmsTemplate>
     */
    #[Computed]
    public function templates()
    {
        return SmsTemplate::orderBy('id')->get();
    }

    /** Whether the outbound SMS channel is armed at all (config kill-switch). */
    #[Computed]
    public function channelEnabled(): bool
    {
        return (bool) config('twilio.notifications.enabled');
    }

    /** Whether Twilio credentials exist, which is what a test send actually needs. */
    #[Computed]
    public function twilioConfigured(): bool
    {
        return app(TwilioService::class)->isConfigured();
    }

    /**
     * The body as a customer would receive it, using the same sample values the
     * A2P campaign was registered with. Reads the unsaved textarea content, so
     * editing updates the preview before saving.
     *
     * @return array{body: string, chars: int, segments: int, encoding: string}
     */
    public function previewFor(int $id): array
    {
        $body = (string) ($this->rows[$id]['body'] ?? '');
        $rendered = SmsTemplate::substitute($body, SmsTemplate::sampleVars());

        return array_merge(['body' => $rendered], SmsTemplate::segmentInfo($rendered));
    }

    public function toggle(int $id): void
    {
        if (! isset($this->rows[$id])) {
            return;
        }

        $this->rows[$id]['is_active'] = ! $this->rows[$id]['is_active'];
        SmsTemplate::whereKey($id)->update(['is_active' => $this->rows[$id]['is_active']]);

        $this->dispatch('saved');
    }

    public function save(int $id): void
    {
        $row = $this->rows[$id] ?? null;
        if (! $row) {
            return;
        }

        $this->validate([
            "rows.{$id}.body" => ['required', 'string', 'max:1000'],
        ]);

        SmsTemplate::whereKey($id)->update(['body' => $row['body']]);
        $this->dispatch('saved');
    }

    /**
     * Send this template, rendered with sample data, to a number the admin types.
     *
     * Consent gating is deliberately skipped: the recipient is a staff phone the
     * admin just entered, not a customer from the database, so there is no opt-in
     * record to check. The channel kill-switch is skipped too, because the whole
     * point of a test is to prove the Twilio setup works BEFORE arming the channel.
     * Credentials are still required — without them there is nothing to test.
     */
    public function sendTest(int $id): void
    {
        $template = SmsTemplate::find($id);
        if (! $template) {
            return;
        }

        $twilio = app(TwilioService::class);

        if (! $twilio->isConfigured()) {
            $this->testResults[$id] = [
                'ok' => false,
                'message' => 'Twilio is not configured on this server — set the credentials first.',
            ];

            return;
        }

        $typed = trim((string) ($this->testNumbers[$id] ?? ''));
        $to = $twilio->normalizeNumber($typed);

        if (! $to) {
            $this->testResults[$id] = [
                'ok' => false,
                'message' => 'Enter a valid 10-digit US mobile number.',
            ];

            return;
        }

        $preview = $this->previewFor($id);
        $sid = $twilio->sendSms($to, $preview['body'], 'test:' . $template->key);

        $this->testResults[$id] = $sid
            ? ['ok' => true, 'message' => 'Test sent to ' . $to . ' (' . $preview['segments'] . ' segment' . ($preview['segments'] === 1 ? '' : 's') . ').']
            : ['ok' => false, 'message' => 'Twilio rejected the send — see Administration → Message Log for the error.'];
    }

    public function render()
    {
        return view('livewire.sms-template-manager');
    }
}
