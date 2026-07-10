<div class="jsl">
    <style>
        .jsl-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; }
        .jsl-head, .jsl-row { display:grid; grid-template-columns: 1fr 70px 110px 100px 34px; gap:8px; align-items:center; }
        .jsl-head { padding-bottom:8px; border-bottom:1px solid #e5e7eb; margin-bottom:6px; }
        .jsl-head span { font-size:11px; font-weight:600; color:#9ca3af; text-transform:uppercase; }
        .jsl-row { padding:6px 0; border-bottom:1px solid #f3f4f6; }
        .jsl-input { padding:7px 10px; font-size:14px; border:1px solid #e5e7eb; border-radius:6px; width:100%; box-sizing:border-box; color:#111827; background:#fff; }
        .jsl-total { font-size:14px; font-weight:600; color:#111827; text-align:right; }
        .jsl-total.tbd { color:#9ca3af; font-weight:500; font-style:italic; }
        .jsl-x { color:#dc2626; border:none; background:none; cursor:pointer; font-size:16px; padding:4px; }
        .jsl-empty { font-size:13px; color:#9ca3af; text-align:center; padding:16px 0; }
        .jsl-add { margin-top:12px; display:flex; gap:8px; align-items:flex-start; }
        .jsl-search { width:100%; padding:8px 12px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; outline:none; box-sizing:border-box; }
        .jsl-drop { position:absolute; z-index:20; top:100%; left:0; right:0; margin-top:4px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.1); max-height:220px; overflow-y:auto; }
        .jsl-drop-item { width:100%; text-align:left; padding:10px 14px; border:none; background:none; cursor:pointer; font-size:13px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; }
        .jsl-custom { padding:8px 14px; font-size:13px; font-weight:600; color:#374151; background:#f9fafb; border:1px solid #d1d5db; border-radius:8px; cursor:pointer; white-space:nowrap; }
        .jsl-foot { display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:12px; border-top:2px solid #e5e7eb; }
        .dark .jsl-card { background:#1f2937; border-color:#374151; }
        .dark .jsl-input, .dark .jsl-search { background:#111827; color:#f9fafb; border-color:#374151; }
        .dark .jsl-total { color:#f9fafb; }
        .dark .jsl-drop, .dark .jsl-custom { background:#111827; color:#f9fafb; border-color:#374151; }
    </style>

    <div class="jsl-card">
        @if (count($lines) > 0)
            <div class="jsl-head">
                <span>Description</span>
                <span style="text-align:center;">Qty</span>
                <span style="text-align:right;">Rate</span>
                <span style="text-align:right;">Total</span>
                <span></span>
            </div>
        @endif

        @foreach ($lines as $i => $line)
            @php $total = $this->lineTotal($line); @endphp
            <div wire:key="jsl-{{ $line['key'] }}" class="jsl-row">
                <input class="jsl-input" type="text" wire:model.blur="lines.{{ $i }}.description"
                    placeholder="{{ $line['service_id'] ? 'Service description' : 'Custom line description' }}" />
                <input class="jsl-input" style="text-align:center;" type="number" min="0" step="0.01"
                    wire:model.blur="lines.{{ $i }}.quantity" />
                <input class="jsl-input" style="text-align:right;" type="number" min="0" step="0.01" placeholder="TBD"
                    title="Leave blank for TBD" wire:model.blur="lines.{{ $i }}.unit_price" />
                <span class="jsl-total {{ $total === null ? 'tbd' : '' }}">
                    {{ $total === null ? 'TBD' : '$' . number_format($total, 2) }}
                </span>
                <button type="button" class="jsl-x" wire:click="removeLine({{ $i }})" title="Remove">&times;</button>
            </div>
        @endforeach

        @if (count($lines) === 0)
            <p class="jsl-empty">No services yet. Search below to add one, or add a custom line.</p>
        @endif

        <div class="jsl-add">
            <div style="flex:1; position:relative;">
                <input class="jsl-search" type="text" placeholder="Search services to add…"
                    wire:model.live.debounce.300ms="serviceSearch"
                    wire:focus="$set('showServiceDropdown', true)" />
                @if ($showServiceDropdown && $this->serviceResults->count() > 0)
                    <div class="jsl-drop">
                        @foreach ($this->serviceResults as $svc)
                            <button type="button" class="jsl-drop-item" wire:click="addService({{ $svc->id }})"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'">
                                <span style="font-weight:500; color:#111827;">{{ $svc->name }}</span>
                                <span style="color:#6b7280;">{{ $svc->default_price === null ? 'TBD' : '$' . number_format((float) $svc->default_price, 2) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <button type="button" class="jsl-custom" wire:click="addCustomLine">+ Custom line</button>
        </div>

        <div class="jsl-foot">
            <span style="font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em;">
                Job Total{{ $this->hasTbdLines ? ' (so far)' : '' }}
            </span>
            <span style="font-size:18px; font-weight:700; color:#111827;">${{ number_format((float) $this->jobTotal, 2) }}</span>
        </div>
        <p style="font-size:11px; color:#9ca3af; margin-top:4px; text-align:right;">
            @if ($this->hasTbdLines)
                Some lines are still TBD and aren't counted yet.
            @else
                Total = sum of Qty × Rate. Leave a rate blank for TBD.
            @endif
        </p>
    </div>
</div>
