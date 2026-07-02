<div class="p-4 space-y-4">
    @php
        $statusClass = fn (string $s) => match ($s) {
            'paid' => 'bg-green-100 text-green-700',
            'sent' => 'bg-yellow-100 text-yellow-700',
            'overdue' => 'bg-red-100 text-red-700',
            'payment_plan' => 'bg-blue-100 text-blue-700',
            'cancelled' => 'bg-gray-100 text-gray-500',
            default => 'bg-gray-100 text-gray-700',
        };
    @endphp

    {{-- Detail view --}}
    @if($this->viewingInvoice)
        @php $inv = $this->viewingInvoice; $balance = $inv->balanceDue(); @endphp
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-brand-500 p-4 text-white flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">{{ $t['invoices'] }}</p>
                    <p class="text-lg font-bold">{{ $inv->invoice_number }}</p>
                </div>
                <button wire:click="close" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $statusClass($inv->status) }}">
                        {{ $inv->status === 'payment_plan' ? 'Payment Plan' : ucfirst($inv->status) }}
                    </span>
                    @if($inv->due_at)
                        <span class="text-sm text-gray-500">Due {{ $inv->due_at->format('M d, Y') }}</span>
                    @endif
                </div>

                @if($inv->lineItems->isNotEmpty())
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Line Items</p>
                        <div class="space-y-2">
                            @foreach($inv->lineItems as $item)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $item->description ?: $item->service?->name ?? 'Service' }}</p>
                                        <p class="text-xs text-gray-500">Qty: {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} × ${{ number_format($item->unit_price, 2) }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">${{ number_format($item->total, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t pt-3 space-y-1 text-sm">
                    <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>${{ number_format($inv->subtotal, 2) }}</span></div>
                    <div class="flex justify-between text-gray-500"><span>Tax</span><span>${{ number_format($inv->tax, 2) }}</span></div>
                    <div class="flex justify-between font-bold text-gray-900 text-base"><span>Total</span><span>${{ number_format($inv->total, 2) }}</span></div>
                    <div class="flex justify-between text-gray-500"><span>Paid</span><span>${{ number_format($inv->amountPaid(), 2) }}</span></div>
                    <div class="flex justify-between font-semibold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}"><span>Balance</span><span>${{ number_format($balance, 2) }}</span></div>
                </div>

                @if($balance > 0 && $inv->share_token && ! in_array($inv->status, ['cancelled']))
                    <a href="{{ $inv->getPublicUrl() }}" target="_blank"
                       class="block text-center bg-brand-500 text-white font-semibold py-3 rounded-lg hover:bg-brand-600 transition-colors">
                        Pay ${{ number_format($balance, 2) }}
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- List --}}
        <h2 class="text-lg font-bold text-gray-900">{{ $t['invoices'] }}</h2>
        @forelse($this->invoices as $inv)
            @php $balance = $inv->balanceDue(); @endphp
            <button wire:click="viewInvoice({{ $inv->id }})" wire:key="inv-{{ $inv->id }}"
                class="w-full text-left bg-white rounded-xl shadow-sm p-4 flex items-center justify-between hover:shadow transition-shadow">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $inv->invoice_number }}</p>
                    <p class="text-xs text-gray-500">{{ $inv->issued_at?->format('M d, Y') ?? '—' }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full {{ $statusClass($inv->status) }}">
                        {{ $inv->status === 'payment_plan' ? 'Payment Plan' : ucfirst($inv->status) }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-900">${{ number_format($inv->total, 2) }}</p>
                    @if($balance > 0)
                        <p class="text-xs text-red-600">${{ number_format($balance, 2) }} due</p>
                    @else
                        <p class="text-xs text-green-600">Paid</p>
                    @endif
                </div>
            </button>
        @empty
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm">No invoices yet.</div>
        @endforelse
    @endif
</div>
