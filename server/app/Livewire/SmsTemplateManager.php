<?php

namespace App\Livewire;

use App\Models\SmsTemplate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Settings → Notifications: edit each customer SMS body (with {placeholders}) and
 * toggle whether that event actually sends. Copy and activation are controlled
 * here so the office never needs a deploy to change a message.
 */
class SmsTemplateManager extends Component
{
    /** Working copy keyed by template id: ['body' => ..., 'is_active' => ...]. */
    public array $rows = [];

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

    public function render()
    {
        return view('livewire.sms-template-manager');
    }
}
