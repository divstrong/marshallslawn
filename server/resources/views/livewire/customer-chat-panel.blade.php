<div>
    <div style="display:flex; flex-direction:column; height:420px; max-height:60vh;">
        {{-- Conversation --}}
        <div style="flex:1; overflow-y:auto; padding:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:flex; flex-direction:column; gap:8px;">
            @forelse ($this->messages as $message)
                @php $mine = $message->sender === \App\Models\CustomerMessage::SENDER_OFFICE; @endphp
                <div style="display:flex; {{ $mine ? 'justify-content:flex-end;' : 'justify-content:flex-start;' }}">
                    <div style="max-width:78%; padding:8px 11px; border-radius:12px; font-size:13px; line-height:1.45; {{ $mine ? 'background:#c9092f; color:#fff; border-bottom-right-radius:4px;' : 'background:#fff; color:#111827; border:1px solid #e5e7eb; border-bottom-left-radius:4px;' }}">
                        <div>{{ $message->body }}</div>
                        <div style="font-size:10px; margin-top:3px; {{ $mine ? 'color:rgba(255,255,255,0.75);' : 'color:#9ca3af;' }}">
                            {{ $mine ? ($message->senderUser?->name ?? 'Office') : 'Customer' }} · {{ $message->created_at?->format('M j, g:i A') }}
                        </div>
                    </div>
                </div>
            @empty
                <div style="margin:auto; color:#9ca3af; font-size:13px; text-align:center;">
                    No messages yet. Say hello to start the conversation.
                </div>
            @endforelse
        </div>

        {{-- Composer --}}
        <form wire:submit="send" style="display:flex; gap:8px; margin-top:10px;">
            <input
                type="text"
                wire:model="body"
                placeholder="Type a message to the customer…"
                autocomplete="off"
                style="flex:1; padding:10px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;"
            />
            <button type="submit" style="padding:10px 18px; font-size:14px; font-weight:600; color:#fff; background:#c9092f; border:none; border-radius:8px; cursor:pointer;">Send</button>
        </form>
    </div>
</div>
