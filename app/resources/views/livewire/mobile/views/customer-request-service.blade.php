<div class="p-4 space-y-4">
    @if($submitted)
        <!-- Success State -->
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">{{ $t['request_submitted'] }}</h2>
            <p class="text-sm text-gray-500 mb-6">We'll review your request and get back to you with an estimate soon.</p>
            <button wire:click="resetForm" class="bg-brand-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors">
                Submit Another Request
            </button>
        </div>
    @else
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <!-- Request Form -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    @include('livewire.mobile.partials.nav-icon', ['icon' => 'plus-circle', 'class' => 'w-8 h-8 text-brand-500'])
                </div>
                <h2 class="text-lg font-bold text-gray-800">{{ $t['request_estimate'] }}</h2>
                <p class="text-sm text-gray-500 mt-1">Tell us what you need and we'll provide a quote</p>
            </div>

            <form wire:submit="submitRequest" class="space-y-4">
                <!-- Select Property -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t['select_property'] }} *</label>
                    <select wire:model="selectedPropertyId" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                        <option value="">-- {{ $t['select_property'] }} --</option>
                        @foreach($this->properties as $property)
                            <option value="{{ $property->id }}">{{ $property->address }}, {{ $property->city }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Select Service -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t['service_type'] }} *</label>
                    <select wire:model="selectedServiceId" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                        <option value="">-- {{ $t['service_type'] }} --</option>
                        @foreach($this->services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}{{ $service->default_price ? ' - $' . number_format($service->default_price, 2) : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Preferred Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t['preferred_date'] }}</label>
                    <input type="date" wire:model="preferredDate" min="{{ now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t['service_description'] }}</label>
                    <textarea wire:model="description" rows="4" placeholder="Describe what you need..." class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="w-full bg-brand-500 text-white py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="submitRequest">{{ $t['submit_request'] }}</span>
                    <span wire:loading wire:target="submitRequest">{{ $t['loading'] }}</span>
                </button>
            </form>
        </div>
    @endif
</div>
