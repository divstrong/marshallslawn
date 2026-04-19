<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — Marshall's Lawn & Landscape</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .header { background: #c9092f; padding: 36px 0; text-align: center; }
        .header img { height: 140px; filter: drop-shadow(1px 0 0 #fff) drop-shadow(-1px 0 0 #fff) drop-shadow(0 1px 0 #fff) drop-shadow(0 -1px 0 #fff) drop-shadow(2px 0 0 #fff) drop-shadow(-2px 0 0 #fff) drop-shadow(0 2px 0 #fff) drop-shadow(0 -2px 0 #fff); }
        .container { max-width: 720px; margin: -20px auto 40px; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .card-body { padding: 32px; }
        .meta-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .invoice-num { font-size: 22px; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .badge-draft { background: #f3f4f6; color: #6b7280; }
        .badge-sent { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }
        .badge-cancelled { background: #f3f4f6; color: #6b7280; }
        .badge-payment_plan { background: #dbeafe; color: #1e40af; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .info-box { background: #f9fafb; border-radius: 8px; padding: 14px 16px; }
        .info-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: #111827; }
        .totals { border-top: 2px solid #e5e7eb; padding-top: 16px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .total-row.grand { padding-top: 12px; margin-top: 8px; border-top: 2px solid #111827; font-size: 20px; font-weight: 700; }
        .total-row.grand .amount { color: #c9092f; }
        .notes { background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .notes-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 6px; }
        .notes-text { font-size: 14px; color: #374151; white-space: pre-line; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        tbody td:last-child { text-align: right; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; background: #d1fae5; color: #065f46; text-align: center; }
        .footer { text-align: center; padding: 24px; font-size: 12px; color: #9ca3af; }
        .pay-section { margin-top: 28px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .pay-header { background: #f9fafb; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; font-size: 15px; font-weight: 600; }
        .pay-body { padding: 20px; }
        .pay-tabs { display: flex; gap: 0; margin-bottom: 20px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .pay-tab { flex: 1; padding: 12px; text-align: center; font-size: 14px; font-weight: 600; cursor: pointer; background: #fff; border: none; color: #6b7280; transition: all 0.15s; }
        .pay-tab.active { background: #c9092f; color: #fff; }
        .pay-field { margin-bottom: 14px; }
        .pay-field label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .pay-field input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #111827; }
        .pay-field input:focus { outline: none; border-color: #c9092f; box-shadow: 0 0 0 3px rgba(201,9,47,0.1); }
        .pay-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-pay { width: 100%; padding: 14px; background: #059669; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 8px; }
        .btn-pay:hover { background: #047857; }
        .toggle-wrap { display: flex; align-items: center; gap: 12px; padding: 16px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; margin-bottom: 20px; cursor: pointer; }
        .toggle-track { position: relative; width: 44px; height: 24px; background: #d1d5db; border-radius: 12px; transition: background 0.2s; flex-shrink: 0; }
        .toggle-track.on { background: #2563eb; }
        .toggle-thumb { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: left 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.15); }
        .toggle-track.on .toggle-thumb { left: 22px; }
        .toggle-label { font-size: 14px; font-weight: 600; color: #1e40af; }
        .toggle-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .plan-summary { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
        .plan-summary .plan-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .plan-summary .plan-row.highlight { font-weight: 700; font-size: 16px; color: #1e40af; border-top: 1px solid #bfdbfe; padding-top: 10px; margin-top: 6px; }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .card-body { padding: 20px; }
            .pay-row { grid-template-columns: 1fr; }
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
                    <span class="invoice-num">{{ $invoice->invoice_number }}</span>
                    <span class="badge badge-{{ $invoice->status }}">
                        {{ $invoice->status === 'payment_plan' ? 'Payment Plan' : ucfirst($invoice->status) }}
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label">Customer</div>
                        <div class="info-value">
                            {{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}
                            @if($invoice->customer->company_name)
                                <br>{{ $invoice->customer->company_name }}
                            @endif
                        </div>
                    </div>
                    @if($invoice->issued_at)
                        <div class="info-box">
                            <div class="info-label">Date Issued</div>
                            <div class="info-value">{{ $invoice->issued_at->format('F j, Y') }}</div>
                        </div>
                    @endif
                    @if($invoice->due_at)
                        <div class="info-box">
                            <div class="info-label">Due Date</div>
                            <div class="info-value" style="{{ $invoice->due_at->isPast() && ! in_array($invoice->status, ['paid', 'payment_plan']) ? 'color: #dc2626; font-weight: 600;' : '' }}">
                                {{ $invoice->due_at->format('F j, Y') }}
                                @if($invoice->due_at->isPast() && ! in_array($invoice->status, ['paid', 'payment_plan']))
                                    — Past Due
                                @endif
                            </div>
                        </div>
                    @endif
                    @if($invoice->paid_at)
                        <div class="info-box">
                            <div class="info-label">Date Paid</div>
                            <div class="info-value" style="color: #059669; font-weight: 600;">{{ $invoice->paid_at->format('F j, Y') }}</div>
                        </div>
                    @endif
                </div>

                {{-- Totals --}}
                <div class="totals">
                    <div class="total-row">
                        <span style="color: #6b7280;">Subtotal</span>
                        <span>${{ number_format($invoice->subtotal, 2) }}</span>
                    </div>
                    @if($invoice->tax > 0)
                        <div class="total-row">
                            <span style="color: #6b7280;">Tax</span>
                            <span>${{ number_format($invoice->tax, 2) }}</span>
                        </div>
                    @endif
                    @if($invoice->credits_total > 0)
                        <div class="total-row">
                            <span style="color: #059669;">Credits Applied</span>
                            <span style="color: #059669;">-${{ number_format($invoice->credits_total, 2) }}</span>
                        </div>
                    @endif
                    <div class="total-row grand">
                        <span>{{ $invoice->status === 'paid' ? 'Total Paid' : 'Amount Due' }}</span>
                        <span class="amount">${{ number_format($invoice->total, 2) }}</span>
                    </div>
                </div>

                {{-- Credits breakdown --}}
                @if($invoice->credits->count() > 0)
                    <div style="margin-top: 24px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Credit</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->credits as $credit)
                                    <tr>
                                        <td style="font-weight: 500;">{{ $credit->code ?: '—' }}</td>
                                        <td>{{ $credit->description }}</td>
                                        <td style="color: #059669; font-weight: 600;">-${{ number_format($credit->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($invoice->notes)
                    <div class="notes" style="margin-top: 24px;">
                        <div class="notes-label">Notes</div>
                        <div class="notes-text">{{ $invoice->notes }}</div>
                    </div>
                @endif

                {{-- Payment section --}}
                @if(in_array($invoice->status, ['sent', 'overdue']))
                    @php
                        $feeRate = 0.0375;
                        $installments = 12;
                        $baseMonthly = round($invoice->total / $installments, 2);
                        $monthlyFee = round($baseMonthly * $feeRate, 2);
                        $monthlyTotal = round($baseMonthly * (1 + $feeRate), 2);
                        $planGrandTotal = $monthlyTotal * $installments;
                        $oneTimeFee = round($invoice->total * $feeRate, 2);
                        $oneTimeWithFee = round($invoice->total + $oneTimeFee, 2);
                    @endphp

                    <div class="pay-section">
                        <div class="pay-header">Pay Invoice</div>
                        <div class="pay-body">

                            {{-- Payment Plan Toggle --}}
                            <div class="toggle-wrap" id="plan-toggle" onclick="togglePaymentPlan()">
                                <div class="toggle-track" id="plan-track">
                                    <div class="toggle-thumb"></div>
                                </div>
                                <div>
                                    <div class="toggle-label">Payment Plan</div>
                                    <div class="toggle-sub">Split into {{ $installments }} monthly payments billed every 30 days</div>
                                </div>
                            </div>

                            {{-- Payment Plan Summary (hidden by default) --}}
                            <div class="plan-summary" id="plan-summary" style="display: none;">
                                <div class="plan-row">
                                    <span style="color: #6b7280;">Invoice Total</span>
                                    <span>${{ number_format($invoice->total, 2) }}</span>
                                </div>
                                <div class="plan-row">
                                    <span style="color: #6b7280;">{{ $installments }} Monthly Payments</span>
                                    <span>${{ number_format($baseMonthly, 2) }}/mo</span>
                                </div>
                                <div class="plan-row">
                                    <span style="color: #6b7280;">CC Processing Fee (3.75%)</span>
                                    <span>${{ number_format($monthlyFee, 2) }}/mo</span>
                                </div>
                                <div class="plan-row highlight">
                                    <span>Monthly Charge</span>
                                    <span>${{ number_format($monthlyTotal, 2) }}/mo</span>
                                </div>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                    Total over {{ $installments }} payments: ${{ number_format($planGrandTotal, 2) }}
                                    &middot; First charge today, then every 30 days via Accept.Blue
                                </div>
                            </div>

                            {{-- CC fee notice for one-time card payment --}}
                            <div id="cc-fee-notice" style="display: none; background: #fefce8; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #92400e;">
                                A 3.75% processing fee (${{ number_format($oneTimeFee, 2) }}) applies to credit card payments.
                                Total charge: <strong>${{ number_format($oneTimeWithFee, 2) }}</strong>
                            </div>

                            {{-- Payment method tabs (hidden when plan is on) --}}
                            <div class="pay-tabs" id="pay-tabs">
                                <button class="pay-tab active" onclick="switchPayTab('card')" id="tab-card">Credit Card</button>
                                <button class="pay-tab" onclick="switchPayTab('ach')" id="tab-ach">ACH / Bank</button>
                            </div>

                            <form id="pay-form" method="POST" action="{{ route('invoice.pay', $invoice->share_token) }}">
                                @csrf
                                <input type="hidden" name="payment_method" id="payment-method" value="card">
                                <input type="hidden" name="payment_plan" id="payment-plan-input" value="0">

                                {{-- Credit Card fields --}}
                                <div id="card-fields">
                                    <div class="pay-field">
                                        <label>Name on Card</label>
                                        <input type="text" name="card_name" placeholder="John Doe" required>
                                    </div>
                                    <div class="pay-field">
                                        <label>Card Number</label>
                                        <input type="text" name="card_number" placeholder="4242 4242 4242 4242" maxlength="19" required>
                                    </div>
                                    <div class="pay-row">
                                        <div class="pay-field">
                                            <label>Expiration</label>
                                            <input type="text" name="card_exp" placeholder="MM / YY" maxlength="7" required>
                                        </div>
                                        <div class="pay-field">
                                            <label>CVC</label>
                                            <input type="text" name="card_cvc" placeholder="123" maxlength="4" required>
                                        </div>
                                    </div>
                                </div>

                                {{-- ACH fields --}}
                                <div id="ach-fields" style="display: none;">
                                    <div class="pay-field">
                                        <label>Account Holder Name</label>
                                        <input type="text" name="ach_name" placeholder="John Doe">
                                    </div>
                                    <div class="pay-field">
                                        <label>Routing Number</label>
                                        <input type="text" name="ach_routing" placeholder="021000021" maxlength="9">
                                    </div>
                                    <div class="pay-field">
                                        <label>Account Number</label>
                                        <input type="text" name="ach_account" placeholder="000123456789">
                                    </div>
                                </div>

                                <button type="submit" class="btn-pay" id="btn-pay">
                                    Pay ${{ number_format($invoice->total, 2) }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <script>
                        let planEnabled = false;
                        let currentTab = 'card';

                        function togglePaymentPlan() {
                            planEnabled = !planEnabled;

                            document.getElementById('plan-track').classList.toggle('on', planEnabled);
                            document.getElementById('plan-summary').style.display = planEnabled ? 'block' : 'none';
                            document.getElementById('payment-plan-input').value = planEnabled ? '1' : '0';

                            if (planEnabled) {
                                // Payment plan = card only, hide tabs, force card
                                document.getElementById('pay-tabs').style.display = 'none';
                                document.getElementById('ach-fields').style.display = 'none';
                                document.getElementById('card-fields').style.display = 'block';
                                document.getElementById('payment-method').value = 'card';
                                document.getElementById('cc-fee-notice').style.display = 'none';
                                document.querySelectorAll('#card-fields input').forEach(i => i.required = true);
                                document.querySelectorAll('#ach-fields input').forEach(i => i.required = false);

                                document.getElementById('btn-pay').textContent = 'Enroll — ${{ number_format($monthlyTotal, 2) }}/mo for {{ $installments }} months';
                            } else {
                                // Restore tabs
                                document.getElementById('pay-tabs').style.display = 'flex';
                                switchPayTab(currentTab);
                                updatePayButton();
                            }
                        }

                        function switchPayTab(method) {
                            currentTab = method;
                            document.getElementById('payment-method').value = method;

                            document.getElementById('card-fields').style.display = method === 'card' ? 'block' : 'none';
                            document.getElementById('ach-fields').style.display = method === 'ach' ? 'block' : 'none';

                            document.getElementById('tab-card').classList.toggle('active', method === 'card');
                            document.getElementById('tab-ach').classList.toggle('active', method === 'ach');

                            document.querySelectorAll('#card-fields input').forEach(i => i.required = method === 'card');
                            document.querySelectorAll('#ach-fields input').forEach(i => i.required = method === 'ach');

                            // Show CC fee notice for one-time card payments
                            document.getElementById('cc-fee-notice').style.display = method === 'card' ? 'block' : 'none';

                            updatePayButton();
                        }

                        function updatePayButton() {
                            if (planEnabled) return;
                            const btn = document.getElementById('btn-pay');
                            if (currentTab === 'card') {
                                btn.textContent = 'Pay ${{ number_format($oneTimeWithFee, 2) }}';
                            } else {
                                btn.textContent = 'Pay ${{ number_format($invoice->total, 2) }}';
                            }
                        }

                        // Show CC fee notice on initial load (card is default)
                        document.addEventListener('DOMContentLoaded', function () {
                            document.getElementById('cc-fee-notice').style.display = 'block';
                            updatePayButton();
                        });
                    </script>

                @elseif($invoice->status === 'payment_plan')
                    <div style="margin-top: 24px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 20px;">
                        <div style="font-size: 15px; font-weight: 700; color: #1e40af; margin-bottom: 12px;">Payment Plan Active</div>
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">
                            <span style="color: #6b7280;">Monthly Payment</span>
                            <span style="font-weight: 600;">${{ number_format($invoice->payment_plan_amount, 2) }}/mo</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">
                            <span style="color: #6b7280;">Payments Made</span>
                            <span style="font-weight: 600;">{{ $invoice->payment_plan_payments_made }} of {{ $invoice->payment_plan_installments }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">
                            <span style="color: #6b7280;">Started</span>
                            <span>{{ $invoice->payment_plan_started_at?->format('F j, Y') }}</span>
                        </div>
                        @if($invoice->payment_plan_payments_made < $invoice->payment_plan_installments)
                            <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px;">
                                <span style="color: #6b7280;">Next Payment</span>
                                <span>{{ $invoice->payment_plan_started_at?->addDays(30 * $invoice->payment_plan_payments_made)->format('F j, Y') }}</span>
                            </div>
                        @endif
                        <div style="margin-top: 12px; font-size: 12px; color: #6b7280;">
                            Billed automatically every 30 days via credit card through Accept.Blue.
                            Includes 3.75% CC processing fee.
                        </div>
                    </div>

                @elseif($invoice->status === 'paid')
                    <div style="margin-top: 24px; text-align: center; padding: 16px; background: #d1fae5; border-radius: 10px; color: #065f46; font-weight: 600;">
                        Invoice Paid — {{ $invoice->paid_at?->format('F j, Y') }}
                    </div>
                @elseif($invoice->status === 'cancelled')
                    <div style="margin-top: 24px; text-align: center; padding: 16px; background: #f3f4f6; border-radius: 10px; color: #6b7280; font-weight: 600;">
                        Invoice Cancelled
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
