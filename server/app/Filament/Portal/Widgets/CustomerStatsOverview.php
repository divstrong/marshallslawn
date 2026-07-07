<?php

namespace App\Filament\Portal\Widgets;

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Job;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $id = Filament::auth()->id();

        $upcoming = Job::where('customer_id', $id)
            ->whereNotNull('scheduled_date')
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->count();

        $estimates = Estimate::where('customer_id', $id)
            ->whereIn('status', ['sent', 'pending', 'draft'])
            ->count();

        $invoices = Invoice::where('customer_id', $id)
            ->whereIn('status', ['sent', 'unpaid', 'overdue', 'partial'])
            ->count();

        return [
            Stat::make('Upcoming Jobs', number_format($upcoming))
                ->description('Scheduled from today')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Open Estimates', number_format($estimates))
                ->description('Awaiting your review')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Unpaid Invoices', number_format($invoices))
                ->description('Balance due')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}
