<div class="flex flex-col h-full" style="height: calc(100vh - 220px); min-height: 360px;">
    <div class="px-4 py-3 border-b border-gray-100 bg-white">
        <h2 class="text-lg font-bold text-gray-900">{{ $t['messages'] ?? 'Messages' }}</h2>
        <p class="text-xs text-gray-500">Chat with the Marshall's Lawn office</p>
    </div>

    {{-- Conversation --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50">
        @forelse($this->messages as $message)
            @php $mine = $message->sender === \App\Models\CustomerMessage::SENDER_CUSTOMER; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] px-3 py-2 rounded-2xl text-sm leading-snug
                    {{ $mine ? 'bg-brand-500 text-white rounded-br-sm' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm' }}">
                    <div>{{ $message->body }}</div>
                    <div class="text-[10px] mt-1 {{ $mine ? 'text-white/70' : 'text-gray-400' }}">
                        {{ $mine ? 'You' : 'Office' }} · {{ $message->created_at?->format('M j, g:i A') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="flex-1 flex items-center justify-center text-center text-gray-400 text-sm py-12">
                No messages yet. Send a note to the office below.
            </div>
        @endforelse
    </div>

    {{-- Composer --}}
    <form wire:submit="send" class="p-3 bg-white border-t border-gray-100 flex gap-2">
        <input
            type="text"
            wire:model="body"
            placeholder="Type a message…"
            autocomplete="off"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-full text-sm focus:outline-none focus:border-brand-500"
        />
        <button type="submit" class="bg-brand-500 text-white font-semibold px-5 rounded-full text-sm hover:bg-brand-600 transition-colors">
            Send
        </button>
    </form>
</div>
