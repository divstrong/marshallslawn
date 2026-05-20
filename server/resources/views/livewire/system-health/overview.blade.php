<div>
    @php
        $groupCounts = collect($results)->map(function ($checks) {
            return [
                'total' => count($checks),
                'passed' => collect($checks)->where('passed', true)->count(),
            ];
        });
        $totalChecks = $groupCounts->sum('total');
        $totalPassed = $groupCounts->sum('passed');
        $allPassed = $totalChecks > 0 && $totalPassed === $totalChecks;
    @endphp

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
        <div>
            <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">System Health Overview</h2>
            <p style="font-size: 13px; color: #6b7280; margin: 0;">
                @if($lastRunAt)
                    Last run: {{ $lastRunAt }} — {{ $totalPassed }} / {{ $totalChecks }} checks passed
                @else
                    Click "Run All Checks" to begin.
                @endif
            </p>
        </div>
        <button wire:click="runAll" wire:loading.attr="disabled" type="button"
                style="padding: 10px 20px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            <span wire:loading.remove wire:target="runAll">Run All Checks</span>
            <span wire:loading wire:target="runAll">Running…</span>
        </button>
    </div>

    @if(! empty($results))
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 24px;">
            @foreach($groupCounts as $group => $counts)
                @php $groupOk = $counts['passed'] === $counts['total']; @endphp
                <div style="background: #fff; border: 1px solid {{ $groupOk ? '#a7f3d0' : '#fecaca' }}; border-radius: 12px; padding: 16px;">
                    <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">{{ $group }}</div>
                    <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 6px;">
                        <span style="font-size: 24px; font-weight: 700; color: {{ $groupOk ? '#065f46' : '#991b1b' }};">{{ $counts['passed'] }}</span>
                        <span style="font-size: 14px; color: #6b7280;">/ {{ $counts['total'] }}</span>
                        <span style="margin-left: auto; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: {{ $groupOk ? '#d1fae5' : '#fee2e2' }}; color: {{ $groupOk ? '#065f46' : '#991b1b' }};">
                            {{ $groupOk ? 'OK' : 'Issues' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        @foreach($results as $group => $checks)
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 16px; overflow: hidden;">
                <div style="padding: 12px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; font-size: 13px; font-weight: 700; color: #111827;">
                    {{ $group }}
                </div>
                <div>
                    @foreach($checks as $check)
                        @include('livewire.system-health.partials.check-row', ['check' => $check])
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div style="background: #fff; border: 1px dashed #d1d5db; border-radius: 12px; padding: 40px; text-align: center;">
            <p style="font-size: 14px; color: #6b7280; margin: 0;">No checks have been run yet.</p>
        </div>
    @endif
</div>
