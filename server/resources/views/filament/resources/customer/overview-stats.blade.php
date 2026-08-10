@include('filament.resources.customer.overview-styles')

@php $snap = $this->snapshot(); @endphp

<div class="cov-tiles">
    <div class="cov-tile is-accent">
        <span class="cov-tile-label">Lifetime revenue</span>
        <span class="cov-tile-value">${{ number_format($snap['lifetimeValue'], 2) }}</span>
        <span class="cov-tile-sub">
            {{ $snap['completedCount'] ? 'Avg $' . number_format($snap['averageJobValue'], 2) . ' per job' : 'No completed work yet' }}
        </span>
    </div>

    {{-- Upcoming work rides along as this tile's byline rather than owning a card. --}}
    <div class="cov-tile is-good">
        <span class="cov-tile-label">Jobs completed</span>
        <span class="cov-tile-value">{{ number_format($snap['completedCount']) }}</span>
        <span class="cov-tile-sub">{{ $this->upcomingByline() }}</span>
    </div>

    <div class="cov-tile {{ $snap['outstanding'] > 0 ? 'is-warn' : '' }}">
        <span class="cov-tile-label">Outstanding</span>
        <span class="cov-tile-value">${{ number_format($snap['outstanding'], 2) }}</span>
        <span class="cov-tile-sub">${{ number_format($snap['paymentsReceived'], 2) }} received to date</span>
    </div>

    <div class="cov-tile">
        <span class="cov-tile-label">Properties</span>
        <span class="cov-tile-value">{{ number_format($snap['properties']->count()) }}</span>
        <span class="cov-tile-sub">
            {{ $snap['openEstimates'] ? $snap['openEstimates'] . ' open ' . \Illuminate\Support\Str::plural('estimate', $snap['openEstimates']) : 'No open estimates' }}
        </span>
    </div>
</div>
