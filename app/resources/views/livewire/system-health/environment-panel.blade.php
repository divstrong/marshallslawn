<div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Environment &amp; Schema</h2>
            <p style="font-size: 13px; color: #6b7280; margin: 0;">
                @if($lastRunAt) Last run: {{ $lastRunAt }} @endif
            </p>
        </div>
        <button wire:click="run" wire:loading.attr="disabled" type="button"
                style="padding: 10px 20px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            <span wire:loading.remove wire:target="run">Re-run Checks</span>
            <span wire:loading wire:target="run">Running…</span>
        </button>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 16px; overflow: hidden;">
        <div style="padding: 12px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">
            Environment
        </div>
        <div>
            @foreach($checks as $check)
                @include('livewire.system-health.partials.check-row', ['check' => $check])
            @endforeach
        </div>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <div style="padding: 12px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">
            Database Schema
        </div>
        <div>
            @foreach($schemaChecks as $check)
                @include('livewire.system-health.partials.check-row', ['check' => $check])
            @endforeach
        </div>
    </div>
</div>
