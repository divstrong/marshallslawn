@include('filament.resources.customer.overview-styles')

@php $snap = $this->snapshot(); @endphp

<div class="cov-scope">
    <div class="cov-list">
        @forelse ($snap['recentCompleted'] as $job)
            @php $on = $job->completed_date ?? $job->scheduled_date; @endphp
            <div class="cov-row">
                @if ($on)
                    @php $date = \Carbon\Carbon::parse($on); @endphp
                    <div class="cov-date">
                        <div class="cov-date-m">{{ $date->format('M') }}</div>
                        <div class="cov-date-d cov-num">{{ $date->format('j') }}</div>
                    </div>
                @endif
                <div class="cov-row-main">
                    <div class="cov-row-title">{{ $job->title ?: 'Job #' . $job->id }}</div>
                    <div class="cov-row-sub">
                        {{ $on ? \Carbon\Carbon::parse($on)->format('M j, Y') : 'Date unknown' }}
                        @if ($job->crew?->name)
                            · {{ $job->crew->name }}
                        @endif
                    </div>
                </div>
                <span class="cov-row-amount cov-num">${{ number_format($job->total(), 2) }}</span>
            </div>
        @empty
            <p class="cov-empty">No completed jobs yet.</p>
        @endforelse

        @if ($snap['completedCount'] > $snap['recentCompleted']->count())
            <p class="cov-empty">
                + {{ $snap['completedCount'] - $snap['recentCompleted']->count() }} more completed —
                see the Jobs tab for the full history.
            </p>
        @endif
    </div>
</div>
