<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDateRange;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Job;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$start, $end] = $this->getDateRange();

        $totalCustomers = Customer::whereBetween('created_at', [$start, $end])->count();
        $newThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();

        $activeJobs = Job::where('status', 'active')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $completedInRange = Job::where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $openEstimates = Estimate::whereIn('status', ['draft', 'sent'])
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $pendingValue = Estimate::whereIn('status', ['draft', 'sent'])
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        return [
            Stat::make('Customers', number_format($totalCustomers))
                ->description($newThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Jobs', number_format($activeJobs))
                ->description($completedInRange . ' completed in range')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('Open Estimates', number_format($openEstimates))
                ->description('$' . number_format($pendingValue, 2) . ' pending value')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
