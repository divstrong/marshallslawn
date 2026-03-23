<div class="p-4 space-y-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @if($showForm)
        <!-- Add Chemical Log Form -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-brand-500 p-4 text-white flex items-center justify-between">
                <p class="text-lg font-bold">{{ $t['add_entry'] }}</p>
                <button wire:click="$set('showForm', false)" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveLog" class="p-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Job *</label>
                    <select wire:model="selectedJobId" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                        <option value="">-- Select Job --</option>
                        @foreach($this->availableJobs as $job)
                            <option value="{{ $job->id }}">{{ $job->title }} - {{ $job->property?->address }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['chemical_name'] }} *</label>
                    <input type="text" wire:model="chemical_name" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['epa_reg'] }}</label>
                        <input type="text" wire:model="epa_registration_number" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['target_pest'] }}</label>
                        <input type="text" wire:model="target_pest" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['application_rate'] }}</label>
                        <input type="text" wire:model="application_rate" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Unit</label>
                        <select wire:model="application_unit" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
                            <option value="oz/1000sqft">oz/1000sqft</option>
                            <option value="lbs/acre">lbs/acre</option>
                            <option value="gal/acre">gal/acre</option>
                            <option value="oz/gal">oz/gal</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['area_treated'] }}</label>
                    <input type="number" wire:model="area_treated" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['wind_speed'] }}</label>
                        <input type="number" wire:model="wind_speed" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['temperature'] }}</label>
                        <input type="number" wire:model="temperature" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t['notes'] }}</label>
                    <textarea wire:model="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>

                <button type="submit" class="w-full bg-brand-500 text-white py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors">
                    {{ $t['save'] }}
                </button>
            </form>
        </div>
    @else
        <!-- Header + Add Button -->
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800">{{ $t['chemical_log'] }}</h2>
            <button wire:click="$set('showForm', true)" class="flex items-center gap-2 bg-brand-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-600 transition-colors">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'plus-circle', 'class' => 'w-4 h-4'])
                {{ $t['add_entry'] }}
            </button>
        </div>

        <!-- Recent Logs -->
        @if($this->recentLogs->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'beaker', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">{{ $t['no_data'] }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->recentLogs as $log)
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $log->chemical_name }}</p>
                                <p class="text-xs text-gray-500">{{ $log->application_date?->format('M d, Y') }}</p>
                            </div>
                            @if($log->epa_registration_number)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">EPA: {{ $log->epa_registration_number }}</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                            @if($log->target_pest)
                                <p><span class="text-gray-400">{{ $t['target_pest'] }}:</span> {{ $log->target_pest }}</p>
                            @endif
                            @if($log->application_rate)
                                <p><span class="text-gray-400">Rate:</span> {{ $log->application_rate }} {{ $log->application_unit }}</p>
                            @endif
                            @if($log->area_treated)
                                <p><span class="text-gray-400">Area:</span> {{ number_format($log->area_treated) }} sqft</p>
                            @endif
                            @if($log->temperature)
                                <p><span class="text-gray-400">Temp:</span> {{ $log->temperature }}°F</p>
                            @endif
                            @if($log->wind_speed)
                                <p><span class="text-gray-400">Wind:</span> {{ $log->wind_speed }} mph</p>
                            @endif
                        </div>
                        @if($log->property)
                            <p class="text-xs text-gray-400 mt-2">{{ $log->property->address }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
