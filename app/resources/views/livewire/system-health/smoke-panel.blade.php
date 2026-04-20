<div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Smoke Tests</h2>
            <p style="font-size: 13px; color: #6b7280; margin: 0;">
                Exercises create &rarr; read &rarr; update &rarr; delete on core models inside a transaction that is rolled back.
                <strong>No records are persisted.</strong>
                @if($lastRunAt) — Last run: {{ $lastRunAt }} @endif
            </p>
        </div>
        <button wire:click="run" wire:loading.attr="disabled" type="button"
                style="padding: 10px 20px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            <span wire:loading.remove wire:target="run">Run Smoke Tests</span>
            <span wire:loading wire:target="run">Running…</span>
        </button>
    </div>

    @if(empty($results))
        <div style="background: #fff; border: 1px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center;">
            <p style="font-size: 14px; color: #6b7280; margin: 0;">Click "Run Smoke Tests" to execute CRUD checks across all core models.</p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($results as $result)
                <div style="background: #fff; border: 1px solid {{ $result['passed'] ? '#a7f3d0' : '#fecaca' }}; border-radius: 12px; overflow: hidden;">
                    <div style="padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: {{ $result['passed'] ? '#ecfdf5' : '#fef2f2' }}; border-bottom: 1px solid {{ $result['passed'] ? '#a7f3d0' : '#fecaca' }};">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            @if($result['passed'])
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 999px; background: #d1fae5; color: #065f46; font-size: 13px; font-weight: 700;">&#10003;</span>
                            @else
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 999px; background: #fee2e2; color: #991b1b; font-size: 13px; font-weight: 700;">&#10005;</span>
                            @endif
                            <span style="font-size: 14px; font-weight: 700; color: #111827;">{{ $result['name'] }}</span>
                            <span style="font-size: 12px; color: #6b7280;">{{ $result['message'] }}</span>
                        </div>
                        <span style="font-size: 11px; color: #9ca3af; flex-shrink: 0;">{{ $result['duration_ms'] }}ms</span>
                    </div>
                    @if(! empty($result['steps']))
                        <div>
                            @foreach($result['steps'] as $step)
                                <div style="display: flex; align-items: center; gap: 10px; padding: 8px 16px 8px 48px; border-bottom: 1px solid #f3f4f6; font-size: 12px;">
                                    @if($step['passed'])
                                        <span style="color: #065f46; font-weight: 700;">&#10003;</span>
                                    @else
                                        <span style="color: #991b1b; font-weight: 700;">&#10005;</span>
                                    @endif
                                    <span style="font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.04em; font-size: 11px;">{{ $step['step'] }}</span>
                                    @if(! empty($step['message']))
                                        <span style="color: #6b7280;">{{ $step['message'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
