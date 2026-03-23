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

                @if($this->viewingJob->property)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['property'] }}</p>
                        <p class="text-sm text-gray-800 mt-1">{{ $this->viewingJob->property->address }}</p>
                        <p class="text-sm text-gray-500">{{ $this->viewingJob->property->city }}, {{ $this->viewingJob->property->state }} {{ $this->viewingJob->property->zip }}</p>
                    </div>
                @endif

                @if($this->viewingJob->description)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-gray-500 uppercase">{{ $t['notes'] }}</p>
                        <p class="text-sm text-gray-700 mt-1">{{ $this->viewingJob->description }}</p>
                    </div>
                @endif

                <!-- Messages -->
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">{{ $t['messages'] }}</p>
                    @if($this->viewingJob->messages->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-3">No messages yet</p>
                    @else
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($this->viewingJob->messages->sortByDesc('created_at')->take(10) as $message)
                                <div class="p-3 rounded-lg {{ $message->sender_type === 'App\\Models\\Customer' ? 'bg-brand-50 ml-4' : 'bg-gray-50 mr-4' }}">
                                    <p class="text-sm text-gray-800">{{ $message->body }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $message->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit="sendMessage" class="mt-3 flex gap-2">
                        <input type="text" wire:model="newMessage" placeholder="{{ $t['send_message'] }}..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg hover:bg-brand-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>

                @if($this->viewingJob->status === 'in_progress')
                    <button wire:click="approveJob({{ $this->viewingJob->id }})" class="w-full bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition-colors">
                        {{ $t['approve_job'] }}
                    </button>
                @endif
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

        <!-- Jobs List -->
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
                                {{ $job->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </div>
                        @if($job->property)
                            <p class="text-xs text-gray-500">{{ $job->property->address }}</p>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            @if($job->scheduled_date)
                                <span>{{ $job->scheduled_date->format('M d, Y') }}</span>
                            @endif
                            @if($job->crew)
                                <span>{{ $job->crew->name }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @endif
</div>
