<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-lg font-semibold text-gray-950 dark:text-white">
            Welcome back, {{ $name }}.
        </p>

        <div class="grid gap-4 sm:grid-cols-3">
            @php
                $cards = [
                    ['label' => 'Upcoming Jobs', 'value' => $upcomingJobs, 'icon' => 'heroicon-o-clipboard-document-list', 'url' => \App\Filament\Portal\Resources\JobResource::getUrl()],
                    ['label' => 'Open Estimates', 'value' => $openEstimates, 'icon' => 'heroicon-o-calculator', 'url' => \App\Filament\Portal\Resources\EstimateResource::getUrl()],
                    ['label' => 'Unpaid Invoices', 'value' => $unpaidInvoices, 'icon' => 'heroicon-o-document-text', 'url' => \App\Filament\Portal\Resources\InvoiceResource::getUrl()],
                ];
            @endphp

            @foreach ($cards as $card)
                <a href="{{ $card['url'] }}" class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                            <div class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                        </div>
                        <x-filament::icon :icon="$card['icon']" class="h-8 w-8 text-primary-500" />
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
