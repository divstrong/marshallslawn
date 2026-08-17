<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dispatch' }} - {{ config('app.name', "Marshall's Lawn") }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fef1f3', 100: '#fde4e8', 200: '#fbc8d1', 300: '#f8a0af',
                            400: '#f4657f', 500: '#e00a35', 600: '#c9092f', 700: '#a80828',
                            800: '#8b0721', 900: '#73061c', 950: '#40020e',
                        }
                    }
                }
            }
        }
    </script>

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .dispatch-shell-header {
            background: #fff;
            border-bottom: 1px solid rgb(229, 231, 235);
        }
        .dispatch-shell-body { background: rgb(243, 244, 246); }
    </style>
</head>
<body class="antialiased dispatch-shell-body">
    <div class="min-h-screen flex flex-col">
        <header class="dispatch-shell-header sticky top-0 z-30 flex items-center justify-between px-4 py-2">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo.png') }}" alt="Marshall's Lawn" class="h-10 w-auto">
                <span class="text-base font-semibold text-gray-800">Dispatch</span>
            </div>
            <div class="flex items-center gap-2">
                {{-- Opens in a new tab: pulling a job off the waiting list is a side
                     errand, and the board's date/crew filters shouldn't be lost to it.
                     Gated on the same permission as the Jobs resource it links to. --}}
                @if (auth()->user()?->hasAccessTo('JobResource'))
                    {{-- The Jobs list pre-filtered to Waiting List, rather than a
                         separate page showing the same rows. The list retitles itself
                         to match the active status filter. --}}
                    <a href="{{ route('filament.admin.resources.jobs.index', ['tableFilters' => ['status' => ['value' => 'waiting_list']]]) }}"
                       target="_blank"
                       rel="noopener"
                       title="Open the waiting list in a new tab"
                       class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-brand-500 hover:text-brand-600 transition">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                        </svg>
                        Waiting List
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                            <path d="M14 4h6v6"/><path d="M20 4l-8 8"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>
                        </svg>
                    </a>
                @endif

                <a href="/"
                   class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-brand-500 hover:text-brand-600 transition">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Admin
                </a>
            </div>
        </header>

        <main class="flex-1 p-4">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
