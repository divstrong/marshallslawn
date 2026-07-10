<div class="ccc" wire:poll.10s>
    {{-- Shares the visual language of the employee chat center (issue #48): same
         avatar header, bubble styles, auto-scroll, composer, and dark mode. --}}
    <style>
        .ccc { --ccc-accent:#c9092f; display:flex; flex-direction:column; height:72vh; min-height:420px; }
        .ccc-header { display:flex; align-items:center; gap:10px; padding:12px 4px 14px; border-bottom:1px solid #e5e7eb; }
        .ccc-avatar { flex-shrink:0; width:38px; height:38px; border-radius:9999px; background:var(--ccc-accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
        .ccc-body { flex:1; overflow-y:auto; padding:16px 4px; display:flex; flex-direction:column; gap:8px; }
        .ccc-bubble { max-width:82%; padding:8px 11px; border-radius:12px; font-size:13px; line-height:1.45; word-wrap:break-word; }
        .ccc-bubble.office { background:var(--ccc-accent); color:#fff; align-self:flex-end; border-bottom-right-radius:4px; }
        .ccc-bubble.customer { background:#fff; color:#0f172a; border:1px solid #e5e7eb; align-self:flex-start; border-bottom-left-radius:4px; }
        .ccc-meta { font-size:10px; opacity:.7; margin-top:4px; }
        .ccc-composer { display:flex; gap:6px; align-items:center; padding-top:12px; border-top:1px solid #e5e7eb; }
        .ccc-input { flex:1; padding:9px 12px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; background:#fff; color:#111827; }
        .ccc-btn { padding:0 16px; height:38px; font-size:13px; font-weight:600; border-radius:8px; border:1px solid var(--ccc-accent); background:var(--ccc-accent); color:#fff; cursor:pointer; }
        .dark .ccc-header,.dark .ccc-composer { border-color:#374151; }
        .dark .ccc-body { background:transparent; }
        .dark .ccc-input { background:#111827; color:#f9fafb; border-color:#374151; }
        .dark .ccc-bubble.customer { background:#0f172a; color:#f1f5f9; border-color:#374151; }
    </style>

    @php $header = $this->header; @endphp

    @if ($header)
        <div class="ccc-header">
            <span class="ccc-avatar">{{ $header['initials'] }}</span>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:14px; color:#111827;">{{ $header['name'] }}</div>
                @if ($header['subtitle'])
                    <div style="font-size:12px; color:#9ca3af;">{{ $header['subtitle'] }}</div>
                @endif
            </div>
        </div>
    @endif

    <div
        class="ccc-body"
        x-data x-ref="body"
        x-init="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
        @customer-chat:updated.window="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
    >
        @forelse ($this->messages as $msg)
            <div class="ccc-bubble {{ $msg['sender'] === 'office' ? 'office' : 'customer' }}">
                @if ($msg['body'])<div>{{ $msg['body'] }}</div>@endif
                <div class="ccc-meta">{{ $msg['sender_name'] }} · {{ $msg['time'] }}</div>
            </div>
        @empty
            <div style="margin:auto; color:#9ca3af; font-size:13px; text-align:center;">
                No messages yet — start the conversation.
            </div>
        @endforelse
    </div>

    <form wire:submit="send" class="ccc-composer">
        <input class="ccc-input" wire:model="body" placeholder="Message {{ $header['name'] ?? 'customer' }}…" autocomplete="off">
        <button type="submit" class="ccc-btn">Send</button>
    </form>
</div>
