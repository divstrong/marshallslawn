<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate {{ $estimate->estimate_number }} — Marshall's Lawn & Landscape</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .header { background: #c9092f; padding: 36px 0; text-align: center; }
        .header img { height: 140px; filter: drop-shadow(1px 0 0 #fff) drop-shadow(-1px 0 0 #fff) drop-shadow(0 1px 0 #fff) drop-shadow(0 -1px 0 #fff) drop-shadow(2px 0 0 #fff) drop-shadow(-2px 0 0 #fff) drop-shadow(0 2px 0 #fff) drop-shadow(0 -2px 0 #fff); }
        .container { max-width: 720px; margin: -20px auto 40px; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .card-body { padding: 32px; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .estimate-num { font-size: 22px; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .badge-draft { background: #f3f4f6; color: #6b7280; }
        .badge-sent { background: #fef3c7; color: #92400e; }
        .badge-accepted { background: #d1fae5; color: #065f46; }
        .badge-declined { background: #fee2e2; color: #991b1b; }
        .badge-expired { background: #f3f4f6; color: #6b7280; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .info-box { background: #f9fafb; border-radius: 8px; padding: 14px 16px; }
        .info-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
        thead th:nth-child(2) { text-align: center; }
        thead th:nth-child(3), thead th:nth-child(4) { text-align: right; }
        tbody td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        tbody td:nth-child(2) { text-align: center; }
        tbody td:nth-child(3), tbody td:nth-child(4) { text-align: right; }
        .totals { border-top: 2px solid #e5e7eb; padding-top: 16px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .total-row.grand { padding-top: 12px; margin-top: 8px; border-top: 2px solid #111827; font-size: 20px; font-weight: 700; }
        .total-row.grand .amount { color: #c9092f; }
        .notes { background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .notes-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; }
        .notes-text { font-size: 14px; color: #374151; white-space: pre-line; }
        .actions { display: flex; gap: 12px; margin-top: 24px; }
        .btn { flex: 1; padding: 14px; text-align: center; border-radius: 10px; font-size: 15px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; }
        .btn-accept { background: #059669; color: #fff; }
        .btn-decline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; background: #d1fae5; color: #065f46; text-align: center; }
        .footer { text-align: center; padding: 24px; font-size: 12px; color: #9ca3af; }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .card-body { padding: 20px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('img/logo.png') }}" alt="Marshall's Lawn & Landscape">
    </div>

    <div class="container">
        <div class="card">
            <div class="card-body">
                @if(session('success'))
                    <div class="flash">{{ session('success') }}</div>
                @endif

                <div class="meta-row">
                    <span class="estimate-num">{{ $estimate->estimate_number }}</span>
                    <span class="badge badge-{{ $estimate->status }}">{{ ucfirst($estimate->status) }}</span>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label">Customer</div>
                        <div class="info-value">
                            {{ $estimate->customer->first_name }} {{ $estimate->customer->last_name }}
                            @if($estimate->customer->company_name)
                                <br>{{ $estimate->customer->company_name }}
                            @endif
                        </div>
                    </div>
                    @if($estimate->property)
                        <div class="info-box">
                            <div class="info-label">Property</div>
                            <div class="info-value">
                                {{ $estimate->property->address }}<br>
                                {{ $estimate->property->city }}, {{ $estimate->property->state }} {{ $estimate->property->zip }}
                            </div>
                        </div>
                    @endif
                    @if($estimate->valid_until)
                        <div class="info-box">
                            <div class="info-label">Valid Until</div>
                            <div class="info-value">{{ $estimate->valid_until->format('F j, Y') }}</div>
                        </div>
                    @endif
                    <div class="info-box">
                        <div class="info-label">Date Issued</div>
                        <div class="info-value">{{ $estimate->created_at->format('F j, Y') }}</div>
                    </div>
                </div>

                {{-- Line items --}}
                @if($estimate->lineItems->count() > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($estimate->lineItems as $item)
                                <tr>
                                    <td style="font-weight: 500;">{{ $item->description ?: ($item->service?->name ?? 'Service') }}</td>
                                    <td>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                    <td>${{ number_format($item->unit_price, 2) }}</td>
                                    <td style="font-weight: 600;">${{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Totals --}}
                <div class="totals">
                    <div class="total-row">
                        <span style="color: #6b7280;">Subtotal</span>
                        <span>${{ number_format($estimate->subtotal, 2) }}</span>
                    </div>
                    @if($estimate->tax > 0)
                        <div class="total-row">
                            <span style="color: #6b7280;">Tax</span>
                            <span>${{ number_format($estimate->tax, 2) }}</span>
                        </div>
                    @endif
                    <div class="total-row grand">
                        <span>Total</span>
                        <span class="amount">${{ number_format($estimate->total, 2) }}</span>
                    </div>
                </div>

                @if($estimate->notes)
                    <div class="notes" style="margin-top: 24px;">
                        <div class="notes-label">Notes</div>
                        <div class="notes-text">{{ $estimate->notes }}</div>
                    </div>
                @endif

                {{-- Accept / Decline buttons --}}
                @if(in_array($estimate->status, ['draft', 'sent']))
                    <div class="actions">
                        <form method="POST" action="{{ route('estimate.accept', $estimate->share_token) }}" style="flex: 1;">
                            @csrf
                            <button type="submit" class="btn btn-accept" style="width: 100%;">Accept Estimate</button>
                        </form>
                        <form method="POST" action="{{ route('estimate.decline', $estimate->share_token) }}" style="flex: 1;">
                            @csrf
                            <button type="submit" class="btn btn-decline" style="width: 100%;">Decline</button>
                        </form>
                    </div>
                @elseif($estimate->status === 'accepted')
                    <div style="margin-top: 24px; text-align: center; padding: 16px; background: #d1fae5; border-radius: 10px; color: #065f46; font-weight: 600;">
                        Estimate Accepted — {{ $estimate->accepted_at?->format('F j, Y') }}
                    </div>
                @elseif($estimate->status === 'declined')
                    <div style="margin-top: 24px; text-align: center; padding: 16px; background: #fee2e2; border-radius: 10px; color: #991b1b; font-weight: 600;">
                        Estimate Declined
                    </div>
                @endif
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Marshall's Lawn & Landscape. All rights reserved.
        </div>
    </div>
</body>
</html>
