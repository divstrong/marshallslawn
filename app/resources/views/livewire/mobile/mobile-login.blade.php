<div class="p-4">
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Login Form -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800">Sign In</h2>
            <p class="text-sm text-gray-500 mt-1">Search for your account to sign in</p>
        </div>

        <form wire:submit="login" class="space-y-4">
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search by name or email..." class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent" autocomplete="off">

                @if($showDropdown && $searchResults->isNotEmpty())
                    <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                        @foreach($searchResults as $result)
                            <button type="button" wire:click="selectUser({{ $result['id'] }}, '{{ $result['type'] }}')" class="w-full px-4 py-3 text-left hover:bg-brand-50 flex items-center justify-between border-b border-gray-100 last:border-0">
                                <div class="min-w-0 flex-1">
                                    @if($result['type'] === 'employee')
                                        <p class="font-medium text-gray-800 truncate">{{ $result['first_name'] ?? '' }} {{ $result['last_name'] ?? '' }}{{ $result['name'] && !$result['first_name'] ? $result['name'] : '' }}</p>
                                    @else
                                        <p class="font-medium text-gray-800 truncate">{{ $result['first_name'] }} {{ $result['last_name'] }}</p>
                                        @if($result['company_name'])
                                            <p class="text-xs text-gray-400 truncate">{{ $result['company_name'] }}</p>
                                        @endif
                                    @endif
                                    @if($result['email'])
                                        <p class="text-sm text-gray-500 truncate">{{ $result['email'] }}</p>
                                    @endif
                                </div>
                                <span class="ml-2 shrink-0 text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $result['type'] === 'employee' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $result['type'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @elseif($showDropdown && $searchResults->isEmpty())
                    <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-gray-500">
                        No results found
                    </div>
                @endif

                @if($selectedUserId)
                    <div class="mt-2 inline-flex items-center bg-brand-500 text-white px-3 py-1 rounded-full text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Selected ({{ $selectedUserType }})
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" wire:model="password" placeholder="Enter password" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">Use "password" for testing</p>
            </div>

            <button type="submit" wire:loading.attr="disabled" class="w-full bg-brand-500 text-white py-3 rounded-lg font-semibold hover:bg-brand-600 transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="login">Sign In</span>
                <span wire:loading wire:target="login">Signing In...</span>
            </button>
        </form>
    </div>
</div>
