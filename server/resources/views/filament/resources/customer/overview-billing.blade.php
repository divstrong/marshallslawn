@include('filament.resources.customer.overview-styles')

@php $snap = $this->snapshot(); @endphp

<div style="display:flex; flex-direction:column; gap:12px;">
    <div>
        <div class="cov-split">
            <span class="cov-split-label">Payments received</span>
            <span class="cov-split-value">${{ number_format($snap['paymentsReceived'], 2) }}</span>
        </div>
        <div class="cov-split">
            <span class="cov-split-label">Outstanding balance</span>
            <span class="cov-split-value {{ $snap['outstanding'] > 0 ? 'is-warn' : '' }}">
                ${{ number_format($snap['outstanding'], 2) }}
            </span>
        </div>
        <div class="cov-split">
            <span class="cov-split-label">Invoices</span>
            <span class="cov-split-value">{{ number_format($snap['invoiceCount']) }}</span>
        </div>
    </div>

    @if ($snap['openInvoices']->isNotEmpty())
        <div class="cov-list">
            @foreach ($snap['openInvoices'] as $invoice)
                @php $overdue = $invoice->due_at && \Carbon\Carbon::parse($invoice->due_at)->isPast(); @endphp
                <div class="cov-row">
                    <div class="cov-row-main">
                        <div class="cov-row-title">{{ $invoice->invoice_number ?: 'Invoice #' . $invoice->id }}</div>
                        <div class="cov-row-sub">
                            {{ $invoice->due_at ? 'Due ' . \Carbon\Carbon::parse($invoice->due_at)->format('M j, Y') : 'No due date' }}
                        </div>
                    </div>
                    @if ($overdue)
                        <span class="cov-pill is-warn">Overdue</span>
                    @endif
                    <span class="cov-row-amount">${{ number_format($invoice->balanceDue(), 2) }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="cov-empty">Nothing outstanding.</p>
    @endif
</div>
