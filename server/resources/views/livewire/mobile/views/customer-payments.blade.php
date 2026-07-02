<div class="p-4 space-y-4">
    <h2 class="text-lg font-bold text-gray-900">{{ $t['payments'] }}</h2>

    <div class="bg-brand-500 rounded-xl p-4 text-white">
        <p class="text-sm opacity-80">Total Paid</p>
        <p class="text-2xl font-bold">${{ number_format($this->totalPaid, 2) }}</p>
    </div>

    @forelse($this->payments as $payment)
        <div wire:key="pay-{{ $payment->id }}" class="bg-white rounded-xl shadow-sm p-4 flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</p>
                <p class="text-xs text-gray-500">
                    {{ $payment->paid_at?->format('M d, Y') ?? '—' }}
                    @if($payment->invoice) · {{ $payment->invoice->invoice_number }} @endif
                </p>
            </div>
            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                {{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}
            </span>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm">No payments recorded yet.</div>
    @endforelse
</div>
