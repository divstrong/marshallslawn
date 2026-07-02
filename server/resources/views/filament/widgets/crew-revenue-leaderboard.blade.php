<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom:12px;">
            <h3 style="font-size:14px; font-weight:600;">Top Crews by Revenue</h3>
            <span style="font-size:12px; color:#6b7280;">
                ${{ number_format($totalRevenue, 0) }} · {{ $totalJobs }} {{ \Illuminate\Support\Str::plural('job', $totalJobs) }}
            </span>
        </div>

        @forelse ($crews as $index => $row)
            @php
                $rank = $index + 1;
                $pct = $maxRevenue > 0 ? max(3, round(($row['revenue'] / $maxRevenue) * 100)) : 0;
                $medal = [1 => '#f59e0b', 2 => '#9ca3af', 3 => '#b45309'][$rank] ?? null;
            @endphp
            <div style="display:flex; align-items:center; gap:10px; padding:8px 0; {{ ! $loop->last ? 'border-bottom:1px solid rgba(0,0,0,0.05);' : '' }}">
                {{-- Rank --}}
                <span style="flex-shrink:0; width:24px; height:24px; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; background:{{ $medal ?? '#6b7280' }};">
                    {{ $rank }}
                </span>

                {{-- Crew + bar --}}
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; gap:8px;">
                        <span style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $row['crew'] }}</span>
                        <span style="font-size:13px; font-weight:700; white-space:nowrap;">${{ number_format($row['revenue'], 2) }}</span>
                    </div>
                    <div style="margin-top:4px; height:6px; background:rgba(0,0,0,0.06); border-radius:9999px; overflow:hidden;">
                        <div style="height:100%; width:{{ $pct }}%; background:{{ $medal ?? '#3b82f6' }}; border-radius:9999px;"></div>
                    </div>
                    <div style="margin-top:3px; font-size:11px; color:#6b7280;">
                        {{ $row['jobs'] }} {{ \Illuminate\Support\Str::plural('job', $row['jobs']) }} completed
                        @if ($row['jobs'] > 0)
                            · ${{ number_format($row['revenue'] / $row['jobs'], 0) }} avg
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p style="font-size:13px; color:#9ca3af; text-align:center; padding:24px 0;">
                No completed jobs in this period yet.
            </p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>
