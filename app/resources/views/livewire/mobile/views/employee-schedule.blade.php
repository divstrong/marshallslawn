<div class="p-4 space-y-4" x-data="{ lat: null, lon: null }" x-init="
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            lat = pos.coords.latitude;
            lon = pos.coords.longitude;
        });
    }
">
    <!-- Date Navigation -->
    <div class="bg-white rounded-xl shadow-sm p-3 flex items-center justify-between">
        <button wire:click="previousDay" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="text-center">
            <button wire:click="goToToday" class="text-sm font-bold text-gray-800 hover:text-brand-500 transition-colors">
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, M d') }}
            </button>
            @if($selectedDate === now()->format('Y-m-d'))
                <p class="text-xs text-brand-500 font-medium">Today</p>
            @endif
        </div>
        <button wire:click="nextDay" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Job Count -->
    <div class="bg-brand-500 rounded-xl p-4 text-white flex items-center justify-between">
        <div>
            <p class="text-sm opacity-80">{{ $t['job_count'] }}</p>
            <p class="text-3xl font-bold">{{ $this->todaysJobs->count() }}</p>
        </div>
        @include('livewire.mobile.partials.nav-icon', ['icon' => 'calendar', 'class' => 'w-12 h-12 opacity-50'])
    </div>

    <!-- GPS Location -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center gap-3">
            @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-5 h-5 text-brand-500'])
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $t['gps_tracking'] }}</p>
                <p class="text-xs text-gray-500" x-text="lat ? lat.toFixed(4) + ', ' + lon.toFixed(4) : 'Requesting location...'">Requesting location...</p>
            </div>
            <div class="ml-auto w-3 h-3 rounded-full bg-green-400 animate-pulse"></div>
        </div>
    </div>

    <!-- Route / Jobs List -->
    @if($this->todaysJobs->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            @include('livewire.mobile.partials.nav-icon', ['icon' => 'calendar', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
            <p class="text-gray-400 mt-3">{{ $t['no_jobs_today'] }}</p>
        </div>
    @else
        <!-- Route Timeline -->
        <div class="space-y-0">
            @foreach($this->todaysJobs as $index => $job)
                <div class="flex gap-3">
                    <!-- Timeline Line -->
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $job->status === 'completed' ? 'bg-green-500 text-white' : ($job->status === 'in_progress' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-600') }}">
                            {{ $index + 1 }}
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 flex-1 min-h-[16px] {{ $job->status === 'completed' ? 'bg-green-300' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>

                    <!-- Job Card -->
                    <button wire:click="$dispatch('navigate-to-view', { view: 'employee_jobs' })" class="flex-1 bg-white rounded-xl shadow-sm p-4 mb-3 text-left hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800">{{ $job->title }}</p>
                                @if($job->customer)
                                    <p class="text-xs text-gray-600 mt-0.5">{{ $job->customer->first_name }} {{ $job->customer->last_name }}</p>
                                @endif
                                @if($job->property)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $job->property->address }}</p>
                                @endif
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $job->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $job->status === 'scheduled' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $job->status === 'in_progress' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </div>
                        @if($job->property)
                            <a href="https://maps.google.com/?q={{ urlencode($job->property->address . ', ' . $job->property->city . ', ' . $job->property->state) }}" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs text-brand-500 font-medium" @click.stop>
                                @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-3 h-3'])
                                {{ $t['navigate'] }}
                            </a>
                        @endif
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
