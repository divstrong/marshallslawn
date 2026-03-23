<div class="p-4 space-y-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if($this->viewingJob)
        <!-- Job Detail -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-brand-500 p-4 text-white flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">{{ $t['job_details'] }}</p>
                    <p class="text-lg font-bold">{{ $this->viewingJob->title }}</p>
                </div>
                <button wire:click="closeJob" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 space-y-4">
                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['status'] }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ ucfirst(str_replace('_', ' ', $this->viewingJob->status)) }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['priority'] }}</p>
                        <p class="text-sm font-semibold mt-1 {{ $this->viewingJob->priority === 'high' ? 'text-red-600' : ($this->viewingJob->priority === 'medium' ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ ucfirst($this->viewingJob->priority ?? 'normal') }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['scheduled_date'] }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $this->viewingJob->scheduled_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['crew'] }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $this->viewingJob->crew?->name ?? '—' }}</p>
                    </div>
                </div>

                <!-- Customer -->
                @if($this->viewingJob->customer)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">Customer</p>
                        <p class="text-sm font-medium text-gray-800 mt-1">{{ $this->viewingJob->customer->first_name }} {{ $this->viewingJob->customer->last_name }}</p>
                        @if($this->viewingJob->customer->phone)
                            <a href="tel:{{ $this->viewingJob->customer->phone }}" class="text-xs text-brand-500">{{ $this->viewingJob->customer->phone }}</a>
                        @endif
                    </div>
                @endif

                <!-- Property / Address -->
                @if($this->viewingJob->property)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['property'] }}</p>
                        <p class="text-sm text-gray-800 mt-1">{{ $this->viewingJob->property->address }}</p>
                        <p class="text-sm text-gray-500">{{ $this->viewingJob->property->city }}, {{ $this->viewingJob->property->state }} {{ $this->viewingJob->property->zip }}</p>
                        <a href="https://maps.google.com/?q={{ urlencode($this->viewingJob->property->address . ', ' . $this->viewingJob->property->city . ', ' . $this->viewingJob->property->state) }}" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs text-brand-500 font-medium">
                            @include('livewire.mobile.partials.nav-icon', ['icon' => 'location', 'class' => 'w-4 h-4'])
                            {{ $t['navigate'] }}
                        </a>
                    </div>
                @endif

                @if($this->viewingJob->description)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['notes'] }}</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $this->viewingJob->description }}</p>
                    </div>
                @endif

                <!-- Media Upload Buttons -->
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ $t['attachments'] }}</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button class="flex flex-col items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            @include('livewire.mobile.partials.nav-icon', ['icon' => 'camera', 'class' => 'w-6 h-6 text-blue-500'])
                            <span class="text-xs text-blue-700 mt-1">{{ $t['upload_photo'] }}</span>
                        </button>
                        <button class="flex flex-col items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span class="text-xs text-purple-700 mt-1">{{ $t['upload_video'] }}</span>
                        </button>
                        <button class="flex flex-col items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                            <span class="text-xs text-green-700 mt-1">{{ $t['record_audio'] }}</span>
                        </button>
                    </div>
                </div>

                <!-- Notes / Messages -->
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ $t['messages'] }}</p>
                    @if($this->viewingJob->messages->isNotEmpty())
                        <div class="space-y-2 max-h-32 overflow-y-auto mb-3">
                            @foreach($this->viewingJob->messages->sortByDesc('created_at')->take(10) as $message)
                                <div class="p-2 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-800">{{ $message->body }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $message->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <form wire:submit="addNote" class="flex gap-2">
                        <input type="text" wire:model="newNote" placeholder="Add a note..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>

                <!-- Status Actions -->
                <div class="flex gap-2">
                    @if($this->viewingJob->status === 'scheduled')
                        <button wire:click="updateJobStatus({{ $this->viewingJob->id }}, 'in_progress')" class="flex-1 bg-yellow-500 text-white py-3 rounded-lg font-semibold hover:bg-yellow-600 transition-colors">
                            Start Job
                        </button>
                    @endif
                    @if($this->viewingJob->status === 'in_progress')
                        <button wire:click="updateJobStatus({{ $this->viewingJob->id }}, 'completed')" class="flex-1 bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                            Complete Job
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @else
        <!-- Filter Tabs -->
        <div class="bg-white rounded-xl shadow-sm p-1 flex gap-1 overflow-x-auto">
            @foreach(['all', 'scheduled', 'in_progress', 'completed'] as $status)
                <button wire:click="$set('filter', '{{ $status }}')" class="flex-1 py-2 px-3 rounded-lg text-xs font-medium transition-all whitespace-nowrap {{ $filter === $status ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $status === 'all' ? 'All' : ucfirst(str_replace('_', ' ', $status)) }}
                </button>
            @endforeach
        </div>

        @if($this->jobs->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'briefcase', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">{{ $t['no_data'] }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->jobs as $job)
                    <button wire:click="viewJob({{ $job->id }})" class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-sm font-bold text-gray-800">{{ $job->title }}</p>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $job->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $job->status === 'scheduled' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $job->status === 'in_progress' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </div>
                        @if($job->customer)
                            <p class="text-xs text-gray-600 font-medium">{{ $job->customer->first_name }} {{ $job->customer->last_name }}</p>
                        @endif
                        @if($job->property)
                            <p class="text-xs text-gray-500">{{ $job->property->address }}, {{ $job->property->city }}</p>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            @if($job->scheduled_date)
                                <span>{{ $job->scheduled_date->format('M d') }}</span>
                            @endif
                            @if($job->priority)
                                <span class="{{ $job->priority === 'high' ? 'text-red-500' : '' }}">{{ ucfirst($job->priority) }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
