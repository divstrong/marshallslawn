<div class="p-4 space-y-4" wire:poll.10s>
    <!-- Clock Display -->
    <div class="bg-white rounded-xl shadow-sm p-6 text-center" x-data="{ time: '' }" x-init="setInterval(() => { time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }, 1000)">
        <p class="text-4xl font-bold text-gray-800 tabular-nums" x-text="time"></p>
        <p class="text-sm text-gray-500 mt-1">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <!-- Hours Today -->
    <div class="bg-brand-500 rounded-xl p-4 text-white flex items-center justify-between">
        <div>
            <p class="text-sm opacity-80">{{ $t['hours_today'] }}</p>
            <p class="text-3xl font-bold tabular-nums">{{ $this->hoursToday }}</p>
        </div>
        @include('livewire.mobile.partials.nav-icon', ['icon' => 'clock', 'class' => 'w-12 h-12 opacity-50'])
    </div>

    <!-- Clock In/Out -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        @if($this->activeShift)
            <!-- Active Shift -->
            <div class="text-center mb-4">
                <div class="w-4 h-4 bg-green-400 rounded-full animate-pulse mx-auto mb-2"></div>
                <p class="text-sm font-medium text-gray-600">{{ $t['current_shift'] }}</p>
                <p class="text-lg font-bold text-gray-800">{{ $t['clock_in'] }}: {{ $this->activeShift->clock_in->format('g:i A') }}</p>
                @if($this->activeShift->break_minutes)
                    <p class="text-xs text-gray-500 mt-1">{{ $t['break_time'] }}: {{ $this->activeShift->break_minutes }} min</p>
                @endif
            </div>

            <div class="space-y-2">
                <button wire:click="clockOut" class="w-full bg-red-500 text-white py-4 rounded-xl font-bold text-lg hover:bg-red-600 transition-colors">
                    {{ $t['clock_out'] }}
                </button>
                <div class="grid grid-cols-3 gap-2">
                    <button wire:click="addBreak(15)" class="bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        +15 min
                    </button>
                    <button wire:click="addBreak(30)" class="bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        +30 min
                    </button>
                    <button wire:click="addBreak(60)" class="bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        +60 min
                    </button>
                </div>
            </div>
        @else
            <!-- No Active Shift -->
            <div class="text-center mb-4">
                <div class="w-4 h-4 bg-gray-300 rounded-full mx-auto mb-2"></div>
                <p class="text-sm text-gray-500">{{ $t['no_active_shift'] }}</p>
            </div>
            <button wire:click="clockIn" class="w-full bg-green-500 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-600 transition-colors">
                {{ $t['clock_in'] }}
            </button>
        @endif
    </div>

    <!-- Today's Logs -->
    @if($this->todaysLogs->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Today</h3>
            <div class="space-y-2">
                @foreach($this->todaysLogs as $log)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                {{ $log->clock_in->format('g:i A') }}
                                @if($log->clock_out)
                                    — {{ $log->clock_out->format('g:i A') }}
                                @else
                                    — <span class="text-green-500 font-medium">Active</span>
                                @endif
                            </p>
                            @if($log->break_minutes)
                                <p class="text-xs text-gray-500">Break: {{ $log->break_minutes }} min</p>
                            @endif
                        </div>
                        <div class="text-right">
                            @php
                                $end = $log->clock_out ?? now();
                                $mins = $log->clock_in->diffInMinutes($end) - ($log->break_minutes ?? 0);
                                $hrs = floor(max(0, $mins) / 60);
                                $m = max(0, $mins) % 60;
                            @endphp
                            <p class="text-sm font-bold text-gray-800">{{ sprintf('%d:%02d', $hrs, $m) }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $log->clock_out ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700' }}">
                                {{ $log->clock_out ? $t['completed'] : 'Active' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent History -->
    @if($this->recentLogs->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t['shift_history'] }}</h3>
            <div class="space-y-2">
                @foreach($this->recentLogs as $log)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $log->clock_in->format('M d') }}</p>
                            <p class="text-xs text-gray-500">{{ $log->clock_in->format('g:i A') }} — {{ $log->clock_out?->format('g:i A') ?? '?' }}</p>
                        </div>
                        @if($log->clock_out)
                            @php
                                $mins = $log->clock_in->diffInMinutes($log->clock_out) - ($log->break_minutes ?? 0);
                                $hrs = floor(max(0, $mins) / 60);
                                $m = max(0, $mins) % 60;
                            @endphp
                            <p class="text-sm font-bold text-gray-800">{{ sprintf('%d:%02d', $hrs, $m) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
