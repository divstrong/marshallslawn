@include('filament.resources.customer.overview-styles')

@php
    $snap = $this->snapshot();
    $today = \Carbon\Carbon::now()->startOfDay();
@endphp

<div class="cov-scope">
    <div class="cov-list">
        @forelse ($snap['upcoming'] as $job)
            @php
                $date = \Carbon\Carbon::parse($job->scheduled_date);
                $days = $today->diffInDays($date, false);
                $when = match (true) {
                    $days <= 0 => 'Today',
                    $days === 1 => 'Tomorrow',
                    $days < 7 => $date->format('l'),
                    default => $date->format('M j, Y'),
                };
            @endphp
            <div class="cov-row">
                <div class="cov-date">
                    <div class="cov-date-m">{{ $date->format('M') }}</div>
                    <div class="cov-date-d cov-num">{{ $date->format('j') }}</div>
                </div>
                <div class="cov-row-main">
                    <div class="cov-row-title">{{ $job->title ?: 'Job #' . $job->id }}</div>
                    <div class="cov-row-sub">
                        {{ $when }}
                        @if ($job->crew?->name)
                            · {{ $job->crew->name }}
                        @else
                            · <span style="color: #b45309;">No crew assigned</span>
                        @endif
                    </div>
                </div>
                <span class="cov-row-amount cov-num">${{ number_format($job->total(), 2) }}</span>
            </div>
        @empty
            <p class="cov-empty">Nothing scheduled ahead.</p>
        @endforelse

        @if ($snap['upcomingCount'] > $snap['upcoming']->count())
            <p class="cov-empty">
                + {{ $snap['upcomingCount'] - $snap['upcoming']->count() }} more scheduled —
                see the Jobs tab for the full run.
            </p>
        @endif

        {{-- Work that exists but has no date is invisible on a date-ordered list,
             and it is exactly what the office needs to chase. --}}
        @if ($snap['unscheduledCount'])
            <p class="cov-empty">
                {{ $snap['unscheduledCount'] }} open
                {{ \Illuminate\Support\Str::plural('job', $snap['unscheduledCount']) }}
                still awaiting a date.
            </p>
        @endif
    </div>
</div>
