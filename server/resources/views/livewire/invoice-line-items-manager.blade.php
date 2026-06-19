<div>
    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
        <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Line Items</label>

        {{-- Header --}}
        @if(count($lines) > 0)
            <div style="display: grid; grid-template-columns: 1fr 70px 100px 100px 36px; gap: 8px; padding: 0 0 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Description</span>
                <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: center;">Qty</span>
                <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: right;">Unit Price</span>
                <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: right;">Total</span>
                <span></span>
            </div>
        @endif

        {{-- Rows --}}
        @foreach($lines as $i => $line)
            <div wire:key="inv-line-{{ $line['id'] ?? $i }}" style="display: grid; grid-template-columns: 1fr 70px 100px 100px 36px; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                <input
                    wire:model.blur="lines.{{ $i }}.description"
                    type="text"
                    placeholder="Description"
                    style="padding: 7px 10px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; width: 100%; box-sizing: border-box;"
                />
                <input
                    wire:model.blur="lines.{{ $i }}.quantity"
                    type="number" min="0" step="0.01"
                    style="padding: 7px 6px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center; width: 100%; box-sizing: border-box;"
                />
                <input
                    wire:model.blur="lines.{{ $i }}.unit_price"
                    type="number" min="0" step="0.01"
                    style="padding: 7px 6px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: right; width: 100%; box-sizing: border-box;"
                />
                <span style="font-size: 14px; color: #111827; text-align: right; font-weight: 500;">${{ number_format((float) $line['total'], 2) }}</span>
                <button
                    wire:click="removeLine({{ $i }})"
                    type="button"
                    style="color: #dc2626; border: none; background: none; cursor: pointer; font-size: 16px; padding: 4px;"
                >&times;</button>
            </div>
        @endforeach

        @if(count($lines) === 0)
            <p style="font-size: 13px; color: #9ca3af; text-align: center; padding: 16px 0;">No line items yet. Search a service below or add a custom line.</p>
        @endif

        {{-- Add controls --}}
        <div style="margin-top: 12px; display: flex; gap: 8px; align-items: flex-start;">
            <div style="flex: 1; position: relative;">
                <input
                    wire:model.live.debounce.300ms="serviceSearch"
                    wire:focus="$set('showServiceDropdown', true)"
                    type="text"
                    placeholder="Search services to add..."
                    style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                />
                @if($showServiceDropdown && $this->serviceResults->count() > 0)
                    <div style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;">
                        @foreach($this->serviceResults as $svc)
                            <button
                                wire:click="addService({{ $svc->id }})"
                                type="button"
                                style="width: 100%; text-align: left; padding: 10px 14px; border: none; background: none; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'"
                            >
                                <span style="font-weight: 500; color: #111827;">{{ $svc->name }}</span>
                                @if($svc->default_price)
                                    <span style="color: #6b7280;">${{ number_format($svc->default_price, 2) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <button
                wire:click="addCustomLine"
                type="button"
                style="padding: 8px 14px; font-size: 13px; font-weight: 600; color: #374151; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; white-space: nowrap;"
            >+ Custom line</button>
        </div>

        {{-- Totals --}}
        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb; max-width: 280px; margin-left: auto;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 13px; color: #6b7280;">Subtotal</span>
                <span style="font-size: 14px; font-weight: 600; color: #111827;">${{ $this->subtotal }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 13px; color: #6b7280;">Tax</span>
                <span style="font-size: 13px; color: #6b7280;">${{ number_format((float) $invoice->tax, 2) }}</span>
            </div>
            @if((float) $invoice->credits_total > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-size: 13px; color: #6b7280;">Credits</span>
                    <span style="font-size: 13px; color: #6b7280;">-${{ number_format((float) $invoice->credits_total, 2) }}</span>
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                <span style="font-size: 14px; font-weight: 700; color: #111827;">Total</span>
                <span style="font-size: 16px; font-weight: 700; color: #111827;">${{ number_format((float) $invoice->total, 2) }}</span>
            </div>
            <p style="font-size: 11px; color: #9ca3af; margin-top: 8px;">Subtotal &amp; total update automatically from the line items above. Tax is set on the General tab.</p>
        </div>
    </div>
</div>
