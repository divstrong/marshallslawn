<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait HasDashboardDateRange
{
    use InteractsWithPageFilters;

    /**
     * Resolve the start/end dates from the dashboard filter form.
     *
     * @return array{Carbon, Carbon}
     */
    protected function getDateRange(): array
    {
        $period = $this->pageFilters['period'] ?? 'this_year';
        $now = Carbon::now();

        return match ($period) {
            'this_month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year'    => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'last_quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'last_year'    => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'custom'       => [
                $this->pageFilters['start_date'] ? Carbon::parse($this->pageFilters['start_date'])->startOfDay() : $now->copy()->startOfYear(),
                $this->pageFilters['end_date'] ? Carbon::parse($this->pageFilters['end_date'])->endOfDay() : $now->copy()->endOfYear(),
            ],
            default        => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }
}
