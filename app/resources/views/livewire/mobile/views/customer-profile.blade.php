<div class="p-4 space-y-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <!-- Avatar Header -->
    <div class="bg-white rounded-xl shadow-sm p-6 text-center">
        @php
            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
        @endphp
        <div class="w-20 h-20 bg-brand-500 rounded-full flex items-center justify-center mx-auto">
            <span class="text-white font-bold text-2xl">{{ $initials }}</span>
        </div>
        <h2 class="text-lg font-bold text-gray-800 mt-3">{{ $first_name }} {{ $last_name }}</h2>
        <p class="text-sm text-gray-500">{{ $email }}</p>
    </div>

    <!-- Edit Form -->
    <form wire:submit="updateProfile" class="bg-white rounded-xl shadow-sm p-4 space-y-3">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ $t['contact_info'] }}</h3>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['first_name'] }}</label>
                <input type="text" wire:model="first_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['last_name'] }}</label>
                <input type="text" wire:model="last_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['email'] }}</label>
            <input type="email" wire:model="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['phone_number'] }}</label>
            <input type="tel" wire:model="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['address'] }}</label>
            <input type="text" wire:model="address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['city'] }}</label>
                <input type="text" wire:model="city" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['state'] }}</label>
                <input type="text" wire:model="state" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['zip'] }}</label>
                <input type="text" wire:model="zip" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="w-full bg-brand-500 text-white py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors disabled:opacity-50">
            <span wire:loading.remove wire:target="updateProfile">{{ $t['update_profile'] }}</span>
            <span wire:loading wire:target="updateProfile">{{ $t['loading'] }}</span>
        </button>
    </form>

    <!-- Properties -->
    @if($this->customer?->properties)
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['my_properties'] }}</h3>
            <div class="space-y-2">
                @foreach($this->customer->properties as $property)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-5 h-5 text-brand-500 flex-shrink-0'])
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $property->address }}</p>
                            <p class="text-xs text-gray-500">{{ $property->city }}, {{ $property->state }} {{ $property->zip }}</p>
                            @if($property->is_primary)
                                <span class="text-xs text-brand-500 font-medium">Primary</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Logout -->
    <button wire:click="logout" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
        {{ $t['logout'] }}
    </button>
</div>
