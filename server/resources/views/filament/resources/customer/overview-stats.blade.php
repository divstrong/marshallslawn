@include('filament.resources.customer.overview-styles')

@php $snap = $this->snapshot(); @endphp

<div class="cov-scope">
    <div class="cov-tiles">
        <div class="cov-tile is-accent">
            <span class="cov-micro">Lifetime revenue</span>
            <span class="cov-tile-value cov-num">${{ number_format($snap['lifetimeValue'], 2) }}</span>
            <span class="cov-tile-sub">
                {{ $snap['completedCount']
                    ? 'Averages $' . number_format($snap['averageJobValue'], 2) . ' per completed job'
                    : 'No completed work yet' }}
            </span>
        </div>

        {{-- Upcoming work rides along as this tile's byline rather than owning a card. --}}
        <div class="cov-tile is-good">
            <span class="cov-micro">Jobs completed</span>
            <span class="cov-tile-value cov-num">{{ number_format($snap['completedCount']) }}</span>
            <span class="cov-tile-sub">{{ $this->upcomingByline() }}</span>
        </div>

        <div class="cov-tile {{ $snap['outstanding'] > 0 ? 'is-warn' : '' }}">
            <span class="cov-micro">Outstanding</span>
            <span class="cov-tile-value cov-num">${{ number_format($snap['outstanding'], 2) }}</span>
            <span class="cov-tile-sub cov-num">
                ${{ number_format($snap['paymentsReceived'], 2) }} received across
                {{ $snap['invoiceCount'] }} {{ \Illuminate\Support\Str::plural('invoice', $snap['invoiceCount']) }}
            </span>
        </div>

        <div class="cov-tile">
            <span class="cov-micro">Properties</span>
            <span class="cov-tile-value cov-num">{{ number_format($snap['properties']->count()) }}</span>
            <span class="cov-tile-sub">
                {{ $snap['openEstimates']
                    ? $snap['openEstimates'] . ' open ' . \Illuminate\Support\Str::plural('estimate', $snap['openEstimates'])
                    : 'No open estimates' }}
            </span>
        </div>
    </div>
</div>
