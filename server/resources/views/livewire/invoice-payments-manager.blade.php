<div class="invoice-payments-manager">
    <style>
        .dark .invoice-payments-manager [style*="background: #fff"] { background:#1f2937 !important; }
        .dark .invoice-payments-manager [style*="background: #f9fafb"] { background:#111827 !important; }
        .dark .invoice-payments-manager [style*="color: #111827"] { color:#f9fafb !important; }
        .dark .invoice-payments-manager [style*="color: #6b7280"],
        .dark .invoice-payments-manager [style*="color: #9ca3af"] { color:#9ca3af !important; }
        .dark .invoice-payments-manager [style*="1px solid #e5e7eb"],
        .dark .invoice-payments-manager [style*="1px solid #d1d5db"],
        .dark .invoice-payments-manager [style*="1px solid #f3f4f6"] { border-color:#374151 !important; }
        .dark .invoice-payments-manager input,
        .dark .invoice-payments-manager select,
        .dark .invoice-payments-manager textarea { color:#f9fafb !important; background:#111827 !important; border-color:#374151 !important; }
        .ipm-input { width:100%; padding:8px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; }
        .ipm-btn { padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#111827; cursor:pointer; }
        .ipm-btn.primary { background:#c9092f; color:#fff; border-color:#c9092f; }
        .ipm-btn.danger { color:#b91c1c; border-color:#fecaca; background:#fff; }
    </style>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
        {{-- Summary --}}
        <div style="display:flex; gap:24px; flex-wrap:wrap; padding:12px 16px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:16px;">
            <div><div style="font-size:11px; color:#9ca3af; text-transform:uppercase;">Invoice total</div><div style="font-size:16px; font-weight:700; color:#111827;">${{ number_format($this->total, 2) }}</div></div>
            <div><div style="font-size:11px; color:#9ca3af; text-transform:uppercase;">Paid</div><div style="font-size:16px; font-weight:700; color:#16a34a;">${{ number_format($this->paid, 2) }}</div></div>
            <div><div style="font-size:11px; color:#9ca3af; text-transform:uppercase;">Balance due</div><div style="font-size:16px; font-weight:700; color:{{ $this->balance > 0 ? '#b45309' : '#16a34a' }};">${{ number_format($this->balance, 2) }}</div></div>
        </div>

        @if (count($lines) > 0)
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                @foreach ($lines as $line)
                    <div style="display:flex; align-items:flex-start; gap:12px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:14px; font-weight:600; color:#111827;">
                                ${{ number_format($line['amount'], 2) }}
                                <span style="font-size:12px; color:#6b7280; font-weight:500;">· {{ $line['method_label'] }}</span>
                            </div>
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">
                                {{ $line['paid_at'] }}{{ $line['reference'] ? ' · Ref ' . $line['reference'] : '' }}{{ $line['recorded_by'] ? ' · ' . $line['recorded_by'] : '' }}
                            </div>
                            @if ($line['notes'])
                                <div style="font-size:12px; color:#6b7280; margin-top:4px; white-space:pre-wrap;">{{ $line['notes'] }}</div>
                            @endif
                        </div>
                        @if ($canManage)
                            <button type="button" class="ipm-btn danger" style="padding:4px 10px;"
                                wire:click="removePayment({{ $line['id'] }})" wire:confirm="Remove this payment?">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div style="font-size:13px; color:#9ca3af; padding:8px 0 16px;">No payments recorded yet.</div>
        @endif

        @if ($canManage)
            <div style="border-top:1px solid #e5e7eb; padding-top:16px;">
                <div style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:10px;">Record payment</div>
                <div style="display:grid; grid-template-columns: 120px 1fr 1fr 1fr auto; gap:8px; align-items:start;">
                    <div>
                        <input class="ipm-input" type="number" step="0.01" wire:model="newAmount" placeholder="Amount">
                        @error('newAmount') <span style="color:#b91c1c; font-size:11px;">{{ $message }}</span> @enderror
                    </div>
                    <select class="ipm-input" wire:model="newMethod">
                        @foreach (\App\Models\Payment::METHODS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div>
                        <input class="ipm-input" type="date" wire:model="newPaidAt">
                        @error('newPaidAt') <span style="color:#b91c1c; font-size:11px;">{{ $message }}</span> @enderror
                    </div>
                    <input class="ipm-input" wire:model="newReference" placeholder="Reference (optional)">
                    <button type="button" class="ipm-btn primary" wire:click="addPayment">Add</button>
                </div>
                <input class="ipm-input" style="margin-top:8px;" wire:model="newNotes" placeholder="Notes (optional)">
            </div>
        @else
            <div style="font-size:12px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:12px;">Only administrators can record or manage payments.</div>
        @endif
    </div>
</div>
