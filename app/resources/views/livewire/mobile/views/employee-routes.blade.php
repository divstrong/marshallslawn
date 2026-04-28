<div class="p-4 space-y-4">
    @if($this->viewingRoute)
        @php $route = $this->viewingRoute; @endphp
        <!-- Route Detail -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-brand-500 p-4 text-white flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-sm opacity-80">{{ $route->route_date->format('l, M j') }}</p>
                    <p class="text-lg font-bold truncate">{{ $route->name }}</p>
                    <p class="text-xs opacity-80 mt-0.5">{{ $route->crew?->name }}</p>
                </div>
                <button wire:click="closeRoute" class="p-2 hover:bg-white/20 rounded-lg transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @php
                $totalStops = $route->stops->count();
                $completedStops = $route->stops->whereIn('status', ['completed', 'skipped'])->count();
                $progressPct = $totalStops > 0 ? (int) round(($completedStops / $totalStops) * 100) : 0;
            @endphp
            <div class="px-4 pt-3">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-medium text-gray-500 uppercase">Progress</p>
                    <p class="text-xs font-semibold text-gray-700">{{ $completedStops }} / {{ $totalStops }}</p>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-500 transition-all" style="width: {{ $progressPct }}%"></div>
                </div>
            </div>

            @if($route->notes)
                <div class="m-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-xs font-medium text-yellow-800 uppercase mb-1">Foreman Notes</p>
                    <p class="text-sm text-yellow-900">{{ $route->notes }}</p>
                </div>
            @endif

            <div class="p-4 space-y-3">
                @if($route->stops->isEmpty())
                    <p class="text-center text-gray-400 py-8">No stops on this route</p>
                @else
                    @foreach($route->stops as $stop)
                        @php
                            $customer = $stop->customer;
                            $property = $stop->property;
                            $customerName = $customer
                                ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                                : 'Unknown';
                            $addressLine = $property
                                ? trim($property->address . ', ' . $property->city . ', ' . $property->state)
                                : null;
                        @endphp
                        <div class="border rounded-xl overflow-hidden
                            {{ $stop->status === 'completed' ? 'border-green-200 bg-green-50/50' : '' }}
                            {{ $stop->status === 'skipped' ? 'border-gray-200 bg-gray-50' : '' }}
                            {{ $stop->status === 'in_progress' ? 'border-yellow-300 bg-yellow-50/50' : '' }}
                            {{ $stop->status === 'pending' ? 'border-gray-200 bg-white' : '' }}">
                            <div class="p-3">
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $stop->status === 'completed' ? 'bg-green-500 text-white' : '' }}
                                        {{ $stop->status === 'skipped' ? 'bg-gray-400 text-white' : '' }}
                                        {{ $stop->status === 'in_progress' ? 'bg-yellow-500 text-white' : '' }}
                                        {{ $stop->status === 'pending' ? 'bg-gray-200 text-gray-700' : '' }}">
                                        @if($stop->status === 'completed')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @elseif($stop->status === 'skipped')
                                            <span>—</span>
                                        @else
                                            {{ $stop->sort_order }}
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-800 truncate">{{ $customerName }}</p>
                                        @if($customer?->company_name)
                                            <p class="text-xs text-gray-500 truncate">{{ $customer->company_name }}</p>
                                        @endif
                                        @if($addressLine)
                                            <a href="https://maps.google.com/?q={{ urlencode($addressLine) }}" target="_blank" class="text-xs text-brand-600 hover:underline truncate block mt-0.5">
                                                {{ $addressLine }}
                                            </a>
                                        @endif
                                        @if($stop->service)
                                            <p class="text-xs font-medium text-gray-600 mt-1">
                                                <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ $stop->service->name }}</span>
                                            </p>
                                        @endif
                                        @if($stop->notes)
                                            <p class="text-xs text-gray-600 mt-2 italic">{{ $stop->notes }}</p>
                                        @endif
                                        @if($customer?->phone)
                                            <a href="tel:{{ $customer->phone }}" class="text-xs text-brand-500 mt-1 inline-block">{{ $customer->phone }}</a>
                                        @endif
                                    </div>
                                </div>

                                <!-- Stop Actions -->
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($stop->status === 'pending')
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'in_progress')" class="flex-1 bg-yellow-500 text-white text-xs font-semibold py-2 rounded-lg hover:bg-yellow-600 transition-colors">Start</button>
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'completed')" class="flex-1 bg-green-500 text-white text-xs font-semibold py-2 rounded-lg hover:bg-green-600 transition-colors">Mark Done</button>
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'skipped')" class="flex-1 bg-gray-300 text-gray-700 text-xs font-semibold py-2 rounded-lg hover:bg-gray-400 transition-colors">Skip</button>
                                    @elseif($stop->status === 'in_progress')
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'completed')" class="flex-1 bg-green-500 text-white text-xs font-semibold py-2 rounded-lg hover:bg-green-600 transition-colors">Complete</button>
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'pending')" class="flex-1 bg-gray-200 text-gray-700 text-xs font-semibold py-2 rounded-lg hover:bg-gray-300 transition-colors">Reset</button>
                                    @elseif($stop->status === 'completed' || $stop->status === 'skipped')
                                        @if($stop->completed_at)
                                            <p class="text-xs text-gray-500">Completed {{ $stop->completed_at->diffForHumans() }}</p>
                                        @endif
                                        <button wire:click="updateStopStatus({{ $stop->id }}, 'pending')" class="ml-auto text-xs text-gray-500 underline">Undo</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @else
        <!-- Filter Tabs -->
        <div class="bg-white rounded-xl shadow-sm p-1 flex gap-1">
            @foreach(['today' => 'Today', 'upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')" class="flex-1 py-2 px-2 rounded-lg text-xs font-medium transition-all {{ $filter === $key ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if($this->crewIds->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'users', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">You're not assigned as a foreman of any crew.</p>
            </div>
        @elseif($this->routes->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">
                    @if($filter === 'today')
                        No routes scheduled for today.
                    @elseif($filter === 'upcoming')
                        No upcoming routes.
                    @elseif($filter === 'past')
                        No past routes.
                    @else
                        No routes assigned.
                    @endif
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->routes as $route)
                    @php
                        $pct = $route->stops_count > 0 ? (int) round(($route->completed_stops_count / $route->stops_count) * 100) : 0;
                    @endphp
                    <button wire:click="viewRoute({{ $route->id }})" class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate">{{ $route->name }}</p>
                                <p class="text-xs text-gray-500">{{ $route->route_date->format('l, M j') }}</p>
                            </div>
                            <span class="ml-2 shrink-0 px-2 py-1 text-xs font-medium rounded-full
                                {{ $route->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $route->status === 'active' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $route->status === 'planning' ? 'bg-gray-100 text-gray-600' : '' }}">
                                {{ ucfirst($route->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-2">{{ $route->crew?->name }}</p>
                        <div class="flex items-center gap-2">
                            <div class="h-2 flex-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-brand-500 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 shrink-0">{{ $route->completed_stops_count }} / {{ $route->stops_count }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
