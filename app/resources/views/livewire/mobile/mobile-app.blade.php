<div class="min-h-screen bg-gray-200 flex items-center justify-center p-4" x-data="{ menuOpen: $wire.entangle('menuOpen') }">
    <!-- Exit Button -->
    <a href="/" class="fixed top-4 right-4 z-50 bg-white rounded-full shadow-lg p-2 hover:bg-gray-100 transition-colors" title="Exit Mobile Preview">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </a>

    <!-- Device Mode Toggle -->
    <div class="fixed top-4 left-4 z-50 bg-white rounded-full shadow-lg p-1 flex gap-1">
        <button wire:click="setDeviceMode('phone')" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all" :class="$wire.deviceMode === 'phone' ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ $t['phone'] }}
        </button>
        <button wire:click="setDeviceMode('tablet')" class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all" :class="$wire.deviceMode === 'tablet' ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            {{ $t['tablet'] }}
        </button>
    </div>

    <!-- Device Frame -->
    <div class="bg-black rounded-[3rem] p-3 shadow-2xl transition-all duration-300" :class="$wire.deviceMode === 'phone' ? 'w-[375px] h-[812px]' : 'w-[1024px] h-[768px]'">
        <div class="w-full h-full bg-white rounded-[2.5rem] overflow-hidden flex flex-col relative">
            <!-- Status Bar -->
            <div class="bg-gray-50 px-6 py-2 flex justify-between items-center text-xs text-gray-600">
                <span>{{ now()->format('g:i A') }}</span>
                <div class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="2" y="7" width="18" height="10" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>
                        <rect x="20" y="10" width="2" height="4" rx="1"/>
                        <rect x="4" y="9" width="10" height="6" rx="1" fill="currentColor"/>
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Hamburger - Tablet Only -->
                    <button x-show="$wire.deviceMode === 'tablet'" x-cloak @click="menuOpen = !menuOpen" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <button wire:click="setView('{{ $this->homeView }}')" class="focus:outline-none">
                        <img src="{{ asset('img/logo.png') }}" alt="Marshall's Lawn" class="h-10 w-auto">
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    @if(session('mobile_app_user_id'))
                        @php
                            $userName = session('mobile_app_user_name', 'User');
                            $nameParts = explode(' ', $userName);
                            $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1) . substr($nameParts[1] ?? '', 0, 1));
                            $userType = session('mobile_app_user_type');
                            $empRole = session('mobile_app_employee_role');
                            $roleLabel = $userType === 'customer' ? 'Customer' : ucfirst($empRole ?? 'Field');
                        @endphp
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">{{ $userName }}</p>
                            <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
                        </div>
                        <button wire:click="setView('{{ $userType === 'customer' ? 'customer_profile' : 'employee_settings' }}')" class="w-10 h-10 bg-brand-500 rounded-full flex items-center justify-center hover:ring-2 hover:ring-brand-300 transition-all">
                            <span class="text-white font-bold text-sm">{{ $initials }}</span>
                        </button>
                    @else
                        <button wire:click="setView('login')" class="flex items-center gap-2 px-3 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium hover:bg-brand-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            {{ $t['login'] }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- Tablet Flyout Menu Overlay -->
            <div x-show="$wire.deviceMode === 'tablet' && menuOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="menuOpen = false" class="absolute inset-0 bg-black/20 z-40" style="top: 85px; border-radius: 0 0 2.5rem 2.5rem;"></div>

            <!-- Tablet Flyout Menu -->
            <div x-show="$wire.deviceMode === 'tablet' && menuOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" @click.stop class="absolute left-0 top-[85px] bottom-0 w-72 bg-white shadow-xl z-50 flex flex-col" style="border-radius: 0 0 0 2.5rem;">
                <div class="p-4 border-b border-gray-100">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $t['navigation'] }}</p>
                </div>
                <nav class="flex-1 p-2 space-y-1 overflow-y-auto">
                    @php
                        if ($this->isCustomer) {
                            $menuItems = [
                                'customer_home' => ['icon' => 'home', 'labelKey' => 'home'],
                                'customer_estimates' => ['icon' => 'document-text', 'labelKey' => 'estimates'],
                                'customer_jobs' => ['icon' => 'briefcase', 'labelKey' => 'jobs'],
                                'customer_request' => ['icon' => 'plus-circle', 'labelKey' => 'request_service'],
                                'customer_profile' => ['icon' => 'user', 'labelKey' => 'profile'],
                            ];
                        } else {
                            $menuItems = [
                                'employee_jobs' => ['icon' => 'briefcase', 'labelKey' => 'jobs'],
                                'employee_schedule' => ['icon' => 'calendar', 'labelKey' => 'schedule'],
                            ];
                            if ($this->isSprayTech || $this->isSupervisor) {
                                $menuItems['employee_chemicals'] = ['icon' => 'beaker', 'labelKey' => 'chemicals'];
                            }
                            $menuItems['employee_time'] = ['icon' => 'clock', 'labelKey' => 'time'];
                            $menuItems['employee_settings'] = ['icon' => 'cog', 'labelKey' => 'settings'];
                        }
                    @endphp
                    @foreach($menuItems as $view => $data)
                        <button wire:click.stop="setViewAndCloseMenu('{{ $view }}')" @click="menuOpen = false" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-all {{ $currentView === $view ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-100' }}">
                            @include('livewire.mobile.partials.nav-icon', ['icon' => $data['icon'], 'class' => 'w-5 h-5'])
                            <span>{{ $t[$data['labelKey']] }}</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto bg-gray-50">
                @switch($currentView)
                    @case('login')
                        <livewire:mobile.mobile-login wire:key="view-login" />
                        @break
                    {{-- Customer Views --}}
                    @case('customer_home')
                        <livewire:mobile.views.customer-home-view :device-mode="$deviceMode" wire:key="view-customer-home" />
                        @break
                    @case('customer_estimates')
                        <livewire:mobile.views.customer-estimates-view :device-mode="$deviceMode" wire:key="view-customer-estimates" />
                        @break
                    @case('customer_jobs')
                        <livewire:mobile.views.customer-jobs-view :device-mode="$deviceMode" wire:key="view-customer-jobs" />
                        @break
                    @case('customer_profile')
                        <livewire:mobile.views.customer-profile-view :device-mode="$deviceMode" wire:key="view-customer-profile" />
                        @break
                    @case('customer_request')
                        <livewire:mobile.views.customer-request-service-view :device-mode="$deviceMode" wire:key="view-customer-request" />
                        @break
                    {{-- Employee Views --}}
                    @case('employee_jobs')
                        <livewire:mobile.views.employee-jobs-view :device-mode="$deviceMode" wire:key="view-employee-jobs" />
                        @break
                    @case('employee_schedule')
                        <livewire:mobile.views.employee-schedule-view :device-mode="$deviceMode" wire:key="view-employee-schedule" />
                        @break
                    @case('employee_chemicals')
                        <livewire:mobile.views.employee-chemicals-view :device-mode="$deviceMode" wire:key="view-employee-chemicals" />
                        @break
                    @case('employee_time')
                        <livewire:mobile.views.employee-time-view :device-mode="$deviceMode" wire:key="view-employee-time" />
                        @break
                    @case('employee_settings')
                        <livewire:mobile.views.employee-settings-view :device-mode="$deviceMode" wire:key="view-employee-settings" />
                        @break
                @endswitch
            </div>

            <!-- Phone: Bottom Navigation -->
            @if(session('mobile_app_user_id'))
                <div x-show="$wire.deviceMode === 'phone'" x-cloak class="bg-white border-t border-gray-200 px-2 py-2 pb-6">
                    <nav class="flex justify-between items-center">
                        @php
                            if ($this->isCustomer) {
                                $navItems = [
                                    'customer_home' => ['icon' => 'home', 'labelKey' => 'home'],
                                    'customer_estimates' => ['icon' => 'document-text', 'labelKey' => 'estimates'],
                                    'customer_jobs' => ['icon' => 'briefcase', 'labelKey' => 'jobs'],
                                    'customer_request' => ['icon' => 'plus-circle', 'labelKey' => 'request_service'],
                                    'customer_profile' => ['icon' => 'user', 'labelKey' => 'profile'],
                                ];
                            } else {
                                $navItems = [
                                    'employee_jobs' => ['icon' => 'briefcase', 'labelKey' => 'jobs'],
                                    'employee_schedule' => ['icon' => 'calendar', 'labelKey' => 'schedule'],
                                ];
                                if ($this->isSprayTech || $this->isSupervisor) {
                                    $navItems['employee_chemicals'] = ['icon' => 'beaker', 'labelKey' => 'chemicals'];
                                }
                                $navItems['employee_time'] = ['icon' => 'clock', 'labelKey' => 'time'];
                            }
                        @endphp
                        @foreach($navItems as $view => $data)
                            <button wire:click="setView('{{ $view }}')" class="flex flex-col items-center justify-center flex-1 py-1 transition-all {{ $currentView === $view ? 'text-brand-500' : 'text-gray-400' }}">
                                @include('livewire.mobile.partials.nav-icon', ['icon' => $data['icon'], 'class' => 'w-6 h-6'])
                                <span class="text-[10px] font-medium mt-1">{{ $t[$data['labelKey']] }}</span>
                            </button>
                        @endforeach
                    </nav>
                </div>
            @endif

            <!-- Home Indicator -->
            <div class="bg-white pb-2 flex justify-center">
                <div class="w-32 h-1 bg-gray-300 rounded-full"></div>
            </div>
        </div>
    </div>
</div>
