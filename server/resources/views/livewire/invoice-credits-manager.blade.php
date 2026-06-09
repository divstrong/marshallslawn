<div class="invoice-credits-manager">
    <style>
        .dark .invoice-credits-manager [style*="background: #fff"] { background:#1f2937 !important; }
        .dark .invoice-credits-manager [style*="color: #111827"] { color:#f9fafb !important; }
        .dark .invoice-credits-manager [style*="color: #6b7280"],
        .dark .invoice-credits-manager [style*="color: #9ca3af"] { color:#9ca3af !important; }
        .dark .invoice-credits-manager [style*="1px solid #e5e7eb"],
        .dark .invoice-credits-manager [style*="1px solid #d1d5db"],
        .dark .invoice-credits-manager [style*="1px solid #f3f4f6"] { border-color:#374151 !important; }
        .dark .invoice-credits-manager input { color:#f9fafb !important; background:#111827 !important; border-color:#374151 !important; }
        .icm-input { width:100%; padding:8px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; }
        .icm-btn { padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#111827; cursor:pointer; }
        .icm-btn.primary { background:#c9092f; color:#fff; border-color:#c9092f; }
        .icm-btn.danger { color:#b91c1c; border-color:#fecaca; background:#fff; }
    </style>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
            <label style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em;">Credits</label>
            <span style="font-size:12px; color:#9ca3af;">{{ count($lines) }} {{ \Illuminate\Support\Str::plural('credit', count($lines)) }} · ${{ number_format($this->creditsTotal, 2) }} applied</span>
        </div>

        @if (count($lines) > 0)
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                @foreach ($lines as $line)
                    <div style="display:flex; align-items:flex-start; gap:12px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:14px; font-weight:600; color:#111827;">
                                {{ $line['description'] }}
                                @if ($line['code'])
                                    <span style="font-size:11px; color:#9ca3af; font-weight:500;">· {{ $line['code'] }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">
                                {{ $line['applied_by'] ? 'By ' . $line['applied_by'] : '' }}{{ $line['created_at'] ? ' · ' . $line['created_at'] : '' }}
                            </div>
                        </div>
                        <div style="font-size:14px; font-weight:700; color:#111827; white-space:nowrap;">${{ number_format($line['amount'], 2) }}</div>
                        @if ($canManage)
                            <button type="button" class="icm-btn danger" style="padding:4px 10px;"
                                wire:click="removeCredit({{ $line['id'] }})" wire:confirm="Remove this credit?">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div style="font-size:13px; color:#9ca3af; padding:8px 0 16px;">No credits applied to this invoice.</div>
        @endif

        @if ($canManage)
            <div style="border-top:1px solid #e5e7eb; padding-top:16px;">
                <div style="font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:10px;">Add credit</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr 120px auto; gap:8px; align-items:start;">
                    <div>
                        <input class="icm-input" wire:model="newDescription" placeholder="Description">
                        @error('newDescription') <span style="color:#b91c1c; font-size:11px;">{{ $message }}</span> @enderror
                    </div>
                    <input class="icm-input" wire:model="newCode" placeholder="Code (optional)">
                    <div>
                        <input class="icm-input" type="number" step="0.01" wire:model="newAmount" placeholder="Amount">
                        @error('newAmount') <span style="color:#b91c1c; font-size:11px;">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" class="icm-btn primary" wire:click="addCredit">Add</button>
                </div>
            </div>
        @else
            <div style="font-size:12px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:12px;">Only administrators can add or manage credits.</div>
        @endif
    </div>
</div>
