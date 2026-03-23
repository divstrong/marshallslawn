<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Employee;
use App\Models\TimeLog;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeTimeView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getActiveShiftProperty(): ?TimeLog
    {
        if (!$this->employee) return null;

        return TimeLog::where('employee_id', $this->employee->id)
            ->whereNull('clock_out')
            ->whereDate('clock_in', now()->toDateString())
            ->latest('clock_in')
            ->first();
    }

    public function getTodaysLogsProperty()
    {
        if (!$this->employee) return collect();

        return TimeLog::where('employee_id', $this->employee->id)
            ->whereDate('clock_in', now()->toDateString())
            ->orderByDesc('clock_in')
            ->get();
    }

    public function getRecentLogsProperty()
    {
        if (!$this->employee) return collect();

        return TimeLog::where('employee_id', $this->employee->id)
            ->whereDate('clock_in', '<', now()->toDateString())
            ->orderByDesc('clock_in')
            ->limit(10)
            ->get();
    }

    public function getHoursTodayProperty(): string
    {
        $total = 0;
        foreach ($this->todaysLogs as $log) {
            $end = $log->clock_out ?? now();
            $minutes = $log->clock_in->diffInMinutes($end) - ($log->break_minutes ?? 0);
            $total += max(0, $minutes);
        }
        $hours = floor($total / 60);
        $mins = $total % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }

    public function clockIn()
    {
        if (!$this->employee || $this->activeShift) return;

        TimeLog::create([
            'employee_id' => $this->employee->id,
            'clock_in' => now(),
            'status' => 'active',
        ]);
    }

    public function clockOut()
    {
        if (!$this->activeShift) return;

        $this->activeShift->update([
            'clock_out' => now(),
            'status' => 'completed',
        ]);
    }

    public function addBreak(int $minutes)
    {
        if (!$this->activeShift) return;

        $this->activeShift->update([
            'break_minutes' => ($this->activeShift->break_minutes ?? 0) + $minutes,
        ]);
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-time', [
            't' => $this->translations,
        ]);
    }
}
