<div class="p-4 space-y-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <!-- Detail View -->
    @if($this->viewingEstimate)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-brand-500 p-4 text-white flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">{{ $t['estimate_number'] }}</p>
                    <p class="text-lg font-bold">{{ $this->viewingEstimate->estimate_number }}</p>
                </div>
                <button wire:click="closeEstimate" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        {{ $this->viewingEstimate->status === 'accepted' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $this->viewingEstimate->status === 'pending' || $this->viewingEstimate->status === 'sent' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $this->viewingEstimate->status === 'declined' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $this->viewingEstimate->status === 'draft' ? 'bg-gray-100 text-gray-700' : '' }}
                    ">
                        {{ ucfirst($this->viewingEstimate->status) }}
                    </span>
                    @if($this->viewingEstimate->valid_until)
                        <span class="text-sm text-gray-500">{{ $t['valid_until'] }}: {{ $this->viewingEstimate->valid_until->format('M d, Y') }}</span>
                    @endif
                </div>

                @if($this->viewingEstimate->property)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['property'] }}</p>
                        <p class="text-sm text-gray-800 mt-1">{{ $this->viewingEstimate->property->address }}</p>
                        <p class="text-sm text-gray-500">{{ $this->viewingEstimate->property->city }}, {{ $this->viewingEstimate->property->state }} {{ $this->viewingEstimate->property->zip }}</p>
                    </div>
                @endif

                <!-- Line Items -->
                @if($this->viewingEstimate->lineItems->isNotEmpty())
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ $t['line_items'] }}</p>
                        <div class="space-y-2">
                            @foreach($this->viewingEstimate->lineItems as $item)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $item->service?->name ?? $item->description ?? 'Service' }}</p>
                                        <p class="text-xs text-gray-500">Qty: {{ $item->quantity }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">${{ number_format($item->total, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Totals -->
                <div class="border-t pt-3 space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ $t['subtotal'] }}</span>
                        <span class="text-gray-800">${{ number_format($this->viewingEstimate->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ $t['tax'] }}</span>
                        <span class="text-gray-800">${{ number_format($this->viewingEstimate->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold border-t pt-2">
                        <span>{{ $t['total'] }}</span>
                        <span class="text-brand-600">${{ number_format($this->viewingEstimate->total, 2) }}</span>
                    </div>
                </div>

                @if($this->viewingEstimate->notes)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['notes'] }}</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $this->viewingEstimate->notes }}</p>
                    </div>
                @endif

                <!-- Actions -->
                @if(in_array($this->viewingEstimate->status, ['pending', 'sent', 'draft']))
                    <div class="flex gap-3">
                        <button wire:click="approveEstimate({{ $this->viewingEstimate->id }})" class="flex-1 bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                            {{ $t['approve'] }}
                        </button>
                        <button wire:click="declineEstimate({{ $this->viewingEstimate->id }})" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                            {{ $t['decline'] }}
                        </button>
                    </div>
                @elseif($this->viewingEstimate->status === 'accepted')
                    <button class="w-full bg-brand-500 text-white py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors">
                        {{ $t['make_payment'] }}
                    </button>
                @endif
            </div>
        </div>
    @else
        <!-- Filter Tabs -->
        <div class="bg-white rounded-xl shadow-sm p-1 flex gap-1 overflow-x-auto">
            @foreach(['all', 'pending', 'accepted', 'declined'] as $status)
                <button wire:click="$set('filter', '{{ $status }}')" class="flex-1 py-2 px-3 rounded-lg text-xs font-medium transition-all whitespace-nowrap {{ $filter === $status ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $status === 'all' ? 'All' : ucfirst($status) }}
                </button>
            @endforeach
        </div>

        <!-- Estimates List -->
        @if($this->estimates->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'document-text', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">{{ $t['no_data'] }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->estimates as $estimate)
                    <button wire:click="viewEstimate({{ $estimate->id }})" class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $estimate->estimate_number }}</p>
                                <p class="text-xs text-gray-500">{{ $estimate->created_at->format('M d, Y') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $estimate->status === 'accepted' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $estimate->status === 'pending' || $estimate->status === 'sent' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $estimate->status === 'declined' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $estimate->status === 'draft' ? 'bg-gray-100 text-gray-700' : '' }}
                            ">
                                {{ ucfirst($estimate->status) }}
                            </span>
                        </div>
                        @if($estimate->property)
                            <p class="text-xs text-gray-500 mb-2">{{ $estimate->property->address }}</p>
                        @endif
                        <p class="text-lg font-bold text-brand-600">${{ number_format($estimate->total, 2) }}</p>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
