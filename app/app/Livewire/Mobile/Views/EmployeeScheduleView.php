<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Employee;
use App\Models\Job;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeScheduleView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $selectedDate;

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getTodaysJobsProperty()
    {
        if (!$this->employee) return collect();

        $crewIds = Crew::where('foreman_id', $this->employee->id)->pluck('id')
            ->merge(CrewMember::where('employee_id', $this->employee->id)->pluck('crew_id'))
            ->unique();

        return Job::whereIn('crew_id', $crewIds)
            ->whereDate('scheduled_date', $this->selectedDate)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->with(['property', 'customer'])
            ->orderBy('scheduled_date')
            ->get();
    }

    public function previousDay()
    {
        $this->selectedDate = \Carbon\Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }

    public function nextDay()
    {
        $this->selectedDate = \Carbon\Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }

    public function goToToday()
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-schedule', [
            't' => $this->translations,
        ]);
    }
}
