<div class="ecc" wire:poll.10s>
    <style>
        .ecc { --ecc-accent:#c9092f; }
        .ecc-grid { display:grid; grid-template-columns: 320px 1fr; gap:16px; height:72vh; min-height:480px; }
        @media (max-width:900px){ .ecc-grid{ grid-template-columns:1fr; height:auto; } }
        .ecc-panel { background:#fff; border:1px solid #e5e7eb; border-radius:12px; display:flex; flex-direction:column; overflow:hidden; }
        .ecc-search { padding:12px; border-bottom:1px solid #e5e7eb; }
        .ecc-input { width:100%; padding:9px 12px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; background:#fff; color:#111827; }
        .ecc-list { overflow-y:auto; flex:1; }
        .ecc-item { display:flex; gap:10px; align-items:center; width:100%; text-align:left; padding:10px 12px; border:0; border-bottom:1px solid #f3f4f6; background:transparent; cursor:pointer; }
        .ecc-item:hover { background:#f9fafb; }
        .ecc-item.on { background:#fef2f4; }
        .ecc-avatar { flex-shrink:0; width:38px; height:38px; border-radius:9999px; background:var(--ecc-accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
        .ecc-badge { flex-shrink:0; background:#dc2626; color:#fff; border-radius:9999px; font-size:11px; font-weight:700; min-width:18px; height:18px; padding:0 5px; display:flex; align-items:center; justify-content:center; }
        .ecc-bubble { max-width:78%; padding:8px 11px; border-radius:12px; font-size:13px; line-height:1.45; word-wrap:break-word; }
        .ecc-bubble.office { background:var(--ecc-accent); color:#fff; align-self:flex-end; border-bottom-right-radius:4px; }
        .ecc-bubble.foreman { background:#fff; color:#0f172a; border:1px solid #e5e7eb; align-self:flex-start; border-bottom-left-radius:4px; }
        .ecc-meta { font-size:10px; opacity:.7; margin-top:4px; }
        .ecc-btn { padding:0 14px; height:38px; font-size:13px; font-weight:600; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#111827; cursor:pointer; }
        .ecc-btn.primary { background:var(--ecc-accent); color:#fff; border-color:var(--ecc-accent); }
        .dark .ecc-panel { background:#1f2937; border-color:#374151; }
        .dark .ecc-search,.dark .ecc-item { border-color:#374151; }
        .dark .ecc-item:hover { background:#111827; }
        .dark .ecc-item.on { background:#3f1d24; }
        .dark .ecc-input,.dark .ecc-btn { background:#111827; color:#f9fafb; border-color:#374151; }
        .dark .ecc-bubble.foreman { background:#0f172a; color:#f1f5f9; border-color:#374151; }
    </style>

    <div class="ecc-grid">
        {{-- Thread list --}}
        <div class="ecc-panel">
            <div class="ecc-search">
                <input class="ecc-input" wire:model.live.debounce.300ms="search" placeholder="Search employees…">
            </div>
            <div class="ecc-list">
                @forelse ($this->threads as $thread)
                    <button type="button" wire:click="selectEmployee({{ $thread['id'] }})" wire:key="thread-{{ $thread['id'] }}"
                        class="ecc-item {{ $selectedEmployeeId === $thread['id'] ? 'on' : '' }}">
                        <span class="ecc-avatar">{{ $thread['initials'] }}</span>
                        <span style="flex:1; min-width:0;">
                            <span style="display:flex; justify-content:space-between; gap:8px;">
                                <span style="font-weight:600; font-size:13px; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $thread['name'] }}</span>
                                @if ($thread['last_at'])<span style="font-size:11px; color:#9ca3af; white-space:nowrap;">{{ $thread['last_at'] }}</span>@endif
                            </span>
                            <span style="display:block; font-size:12px; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $thread['last_preview'] ?? 'No messages yet' }}</span>
                        </span>
                        @if ($thread['unread'] > 0)<span class="ecc-badge">{{ $thread['unread'] > 9 ? '9+' : $thread['unread'] }}</span>@endif
                    </button>
                @empty
                    <div style="padding:24px; text-align:center; color:#9ca3af; font-size:13px;">
                        {{ trim($search) !== '' ? 'No employees match.' : 'No conversations yet. Search to start one.' }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Conversation --}}
        <div class="ecc-panel">
            @if (! $this->selectedEmployee)
                <div style="margin:auto; text-align:center; color:#9ca3af; font-size:14px; padding:32px;">
                    Select an employee to view the conversation.
                </div>
            @else
                @php $emp = $this->selectedEmployee; @endphp
                <div style="display:flex; align-items:center; gap:10px; padding:12px 16px; border-bottom:1px solid #e5e7eb;">
                    <span class="ecc-avatar">{{ $emp['initials'] }}</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:14px; color:#111827;">{{ $emp['name'] }}</div>
                        <div style="font-size:12px; color:#9ca3af;">{{ ucfirst((string) $emp['role']) }}@if ($emp['phone']) · {{ $emp['phone'] }}@endif</div>
                    </div>
                    <button type="button" wire:click="clearSelection" class="ecc-btn" style="height:30px; padding:0 10px; font-size:12px;">Close</button>
                </div>

                <div
                    style="flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:8px; background:transparent;"
                    x-data x-ref="body"
                    x-init="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
                    @chat-center:updated.window="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
                    wire:key="ecc-body-{{ $emp['id'] }}"
                >
                    @forelse ($this->messages as $msg)
                        <div class="ecc-bubble {{ $msg['sender'] === 'office' ? 'office' : 'foreman' }}">
                            @if ($msg['attachment_url'])
                                @if ($msg['attachment_type'] === 'video')
                                    <video src="{{ $msg['attachment_url'] }}" controls style="max-width:240px; border-radius:8px; display:block; margin-bottom:6px;"></video>
                                @elseif ($msg['attachment_type'] === 'file')
                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener" download
                                        style="display:flex; align-items:center; gap:8px; padding:8px 10px; margin-bottom:6px; border-radius:8px; background:rgba(15,23,42,0.06); text-decoration:none; color:inherit; max-width:240px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                                        <span style="font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $msg['attachment_name'] ?: 'Download file' }}</span>
                                    </a>
                                @else
                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener">
                                        <img src="{{ $msg['attachment_url'] }}" alt="attachment" style="max-width:240px; border-radius:8px; display:block; margin-bottom:6px;">
                                    </a>
                                @endif
                            @endif
                            @if ($msg['body'])<div>{{ $msg['body'] }}</div>@endif
                            <div class="ecc-meta">{{ $msg['sender_name'] }} · {{ $msg['time'] }}</div>
                        </div>
                    @empty
                        <div style="margin:auto; color:#9ca3af; font-size:13px;">No messages yet — start the conversation.</div>
                    @endforelse
                </div>

                <div style="padding:12px 16px; border-top:1px solid #e5e7eb;">
                    <div wire:loading wire:target="attachment" style="font-size:11px; color:#9ca3af; margin-bottom:6px;">Uploading attachment…</div>
                    <form wire:submit="send" style="display:flex; gap:6px; align-items:center;">
                        <label title="Attach a photo, video, or file" style="height:38px; width:38px; flex-shrink:0; display:flex; align-items:center; justify-content:center; border:1px solid #d1d5db; border-radius:8px; cursor:pointer; color:#6b7280;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                            <input type="file" wire:model="attachment" style="display:none;">
                        </label>
                        <input class="ecc-input" wire:model="body" placeholder="Message {{ $emp['name'] }}…" autocomplete="off" style="flex:1;">
                        <button type="submit" class="ecc-btn primary">Send</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
