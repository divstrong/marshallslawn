<div class="p-4 space-y-4">
    <!-- Profile Card -->
    @if($this->employee)
        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            @php
                $name = trim(($this->employee->first_name ?? '') . ' ' . ($this->employee->last_name ?? '')) ?: $this->employee->name;
                $initials = strtoupper(substr($this->employee->first_name ?? $this->employee->name ?? '', 0, 1) . substr($this->employee->last_name ?? '', 0, 1));
            @endphp
            <div class="w-20 h-20 bg-brand-500 rounded-full flex items-center justify-center mx-auto">
                <span class="text-white font-bold text-2xl">{{ $initials }}</span>
            </div>
            <h2 class="text-lg font-bold text-gray-800 mt-3">{{ $name }}</h2>
            <p class="text-sm text-gray-500">{{ $this->employee->email }}</p>
            <div class="flex justify-center gap-2 mt-2">
                <span class="px-3 py-1 bg-brand-50 text-brand-700 rounded-full text-xs font-medium">
                    {{ ucfirst(session('mobile_app_employee_role', 'field')) }}
                </span>
                @if($this->employee->division)
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                        {{ $this->employee->division }}
                    </span>
                @endif
            </div>
        </div>
    @endif

    <!-- Language -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['language_setting'] }}</h3>
        <div class="flex gap-2">
            <button wire:click="setLanguage('en')" class="flex-1 py-3 rounded-lg text-sm font-semibold transition-all {{ $language === 'en' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $t['english'] }}
            </button>
            <button wire:click="setLanguage('es')" class="flex-1 py-3 rounded-lg text-sm font-semibold transition-all {{ $language === 'es' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $t['spanish'] }}
            </button>
        </div>
    </div>

    <!-- Settings Toggles -->
    <div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'bell', 'class' => 'w-5 h-5 text-gray-400'])
                <span class="text-sm font-medium text-gray-800">{{ $t['notifications_setting'] }}</span>
            </div>
            <button wire:click="$toggle('notificationsEnabled')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $notificationsEnabled ? 'bg-brand-500' : 'bg-gray-300' }}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $notificationsEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-5 h-5 text-gray-400'])
                <span class="text-sm font-medium text-gray-800">{{ $t['gps_tracking'] }}</span>
            </div>
            <button wire:click="$toggle('gpsEnabled')" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $gpsEnabled ? 'bg-brand-500' : 'bg-gray-300' }}">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $gpsEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    <!-- Contact Info -->
    @if($this->employee)
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['contact_info'] }}</h3>
            <div class="space-y-2 text-sm">
                @if($this->employee->phone)
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 w-16">{{ $t['phone_number'] }}</span>
                        <a href="tel:{{ $this->employee->phone }}" class="text-brand-500">{{ $this->employee->phone }}</a>
                    </div>
                @endif
                @if($this->employee->email)
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 w-16">{{ $t['email'] }}</span>
                        <span class="text-gray-800">{{ $this->employee->email }}</span>
                    </div>
                @endif
                @if($this->employee->address)
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 w-16">{{ $t['address'] }}</span>
                        <span class="text-gray-800">{{ $this->employee->address }}, {{ $this->employee->city }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- App Info -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">{{ $t['app_version'] }}</span>
            <span class="text-gray-800 font-medium">1.0.0-beta</span>
        </div>
    </div>

    <!-- Logout -->
    <button wire:click="logout" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
        {{ $t['sign_out'] }}
    </button>
</div>
