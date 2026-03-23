<div class="p-4 space-y-4" x-data="{ lat: null, lon: null }" x-init="
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            lat = pos.coords.latitude;
            lon = pos.coords.longitude;
        });
    }
">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-brand-500 to-brand-700 rounded-2xl p-5 text-white">
        <p class="text-sm opacity-80">{{ $t['welcome_back'] }}</p>
        <h1 class="text-2xl font-bold mt-1">{{ session('mobile_app_user_name') }}</h1>
        @if($this->customer)
            <p class="text-sm opacity-80 mt-1">{{ $this->customer->address }}, {{ $this->customer->city }}</p>
        @endif
    </div>

    <!-- Weather Widget -->
    @if($weather)
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @include('livewire.mobile.partials.nav-icon', ['icon' => 'sun', 'class' => 'w-10 h-10 text-yellow-500'])
                    <div>
                        <p class="text-2xl font-bold text-gray-800">{{ $weather['temp'] }}°F</p>
                        <p class="text-sm text-gray-500">{{ $weather['condition'] }}</p>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p>{{ $t['humidity'] }}: {{ $weather['humidity'] }}%</p>
                    <p>{{ $t['wind'] }}: {{ $weather['wind'] }} mph</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['quick_actions'] }}</h3>
        <div class="grid grid-cols-2 gap-3">
            <button wire:click="$dispatch('navigate-to-view', { view: 'customer_request' })" class="flex flex-col items-center justify-center p-4 bg-brand-50 rounded-xl hover:bg-brand-100 transition-colors">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'plus-circle', 'class' => 'w-8 h-8 text-brand-500'])
                <span class="text-xs font-medium text-brand-700 mt-2">{{ $t['request_service'] }}</span>
            </button>
            <button wire:click="$dispatch('navigate-to-view', { view: 'customer_estimates' })" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'document-text', 'class' => 'w-8 h-8 text-blue-500'])
                <span class="text-xs font-medium text-blue-700 mt-2">{{ $t['estimates'] }}</span>
            </button>
            <button wire:click="$dispatch('navigate-to-view', { view: 'customer_jobs' })" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-colors">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'briefcase', 'class' => 'w-8 h-8 text-green-500'])
                <span class="text-xs font-medium text-green-700 mt-2">{{ $t['jobs'] }}</span>
            </button>
            <button wire:click="$dispatch('navigate-to-view', { view: 'customer_profile' })" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'user', 'class' => 'w-8 h-8 text-purple-500'])
                <span class="text-xs font-medium text-purple-700 mt-2">{{ $t['profile'] }}</span>
            </button>
        </div>
    </div>

    <!-- Upcoming Services -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['upcoming_services'] }}</h3>
        @if($this->upcomingJobs->isEmpty())
            <div class="text-center py-6">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'calendar', 'class' => 'w-12 h-12 text-gray-300 mx-auto'])
                <p class="text-sm text-gray-400 mt-2">{{ $t['no_upcoming'] }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->upcomingJobs as $job)
                    <button wire:click="$dispatch('navigate-to-view', { view: 'customer_jobs' })" class="w-full flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-left">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $job->status === 'in_progress' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' }}">
                            <span class="text-xs font-bold">{{ $job->scheduled_date?->format('M') }}<br>{{ $job->scheduled_date?->format('d') }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $job->title }}</p>
                            <p class="text-xs text-gray-500">{{ $job->property?->address }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $job->status === 'in_progress' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['recent_notifications'] }}</h3>
        @if($this->notifications->isEmpty())
            <div class="text-center py-6">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'bell', 'class' => 'w-12 h-12 text-gray-300 mx-auto'])
                <p class="text-sm text-gray-400 mt-2">{{ $t['no_data'] }}</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($this->notifications as $notification)
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">
                            @include('livewire.mobile.partials.nav-icon', ['icon' => 'bell', 'class' => 'w-4 h-4 text-brand-500'])
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $notification->title }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $notification->body }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
