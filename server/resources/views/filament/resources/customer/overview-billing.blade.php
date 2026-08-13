@include('filament.resources.customer.overview-styles')

@php
    $snap = $this->snapshot();
    // Share of everything billed that has actually been collected. Invoiced is
    // derived rather than queried so the bar can never exceed 100%.
    $invoiced = $snap['paymentsReceived'] + $snap['outstanding'];
    $collectedPct = $invoiced > 0 ? min(100, round($snap['paymentsReceived'] / $invoiced * 100)) : 0;
@endphp

<div class="cov-scope">
    <div style="display:flex; flex-direction:column; gap:14px;">
        <div class="cov-balance">
            <span class="cov-micro">Outstanding balance</span>
            <div class="cov-balance-value cov-num {{ $snap['outstanding'] > 0 ? 'is-warn' : '' }}">
                ${{ number_format($snap['outstanding'], 2) }}
            </div>
            @if ($invoiced > 0)
                <div class="cov-meter" role="img"
                    aria-label="{{ $collectedPct }}% of billed work has been collected">
                    <div class="cov-meter-fill" style="width: {{ $collectedPct }}%;"></div>
                </div>
                <div class="cov-row-sub cov-num" style="margin-top:6px;">
                    ${{ number_format($snap['paymentsReceived'], 2) }} collected of
                    ${{ number_format($invoiced, 2) }} billed ({{ $collectedPct }}%)
                </div>
            @else
                <div class="cov-row-sub" style="margin-top:6px;">Nothing invoiced yet.</div>
            @endif
        </div>

        <div>
            <div class="cov-split">
                <span class="cov-split-label">Payments received</span>
                <span class="cov-split-value cov-num">${{ number_format($snap['paymentsReceived'], 2) }}</span>
            </div>
            <div class="cov-split">
                <span class="cov-split-label">Invoices</span>
                <span class="cov-split-value cov-num">{{ number_format($snap['invoiceCount']) }}</span>
            </div>
            <div class="cov-split">
                <span class="cov-split-label">Open estimates</span>
                <span class="cov-split-value cov-num">{{ number_format($snap['openEstimates']) }}</span>
            </div>
        </div>

        @if ($snap['openInvoices']->isNotEmpty())
            <div>
                <div class="cov-micro" style="margin-bottom:8px;">Open invoices</div>
                <div class="cov-list">
                    @foreach ($snap['openInvoices'] as $invoice)
                        @php $overdue = $invoice->due_at && \Carbon\Carbon::parse($invoice->due_at)->isPast(); @endphp
                        <div class="cov-row">
                            <div class="cov-row-main">
                                <div class="cov-row-title cov-num">{{ $invoice->invoice_number ?: 'Invoice #' . $invoice->id }}</div>
                                <div class="cov-row-sub">
                                    {{ $invoice->due_at ? 'Due ' . \Carbon\Carbon::parse($invoice->due_at)->format('M j, Y') : 'No due date' }}
                                </div>
                            </div>
                            @if ($overdue)
                                <span class="cov-pill is-warn">Overdue</span>
                            @endif
                            <span class="cov-row-amount cov-num">${{ number_format($invoice->balanceDue(), 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="cov-empty">Nothing outstanding.</p>
        @endif
    </div>
</div>
