<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\ChemicalLog;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Employee;
use App\Models\Job;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeChemicalsView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public bool $showForm = false;
    public ?int $selectedJobId = null;
    public string $chemical_name = '';
    public string $epa_registration_number = '';
    public string $target_pest = '';
    public string $application_rate = '';
    public string $application_unit = 'oz/1000sqft';
    public string $area_treated = '';
    public string $wind_speed = '';
    public string $temperature = '';
    public string $notes = '';

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getRecentLogsProperty()
    {
        if (!$this->employee) return collect();

        return ChemicalLog::where('employee_id', $this->employee->id)
            ->with(['job', 'property'])
            ->orderByDesc('application_date')
            ->limit(20)
            ->get();
    }

    public function getAvailableJobsProperty()
    {
        if (!$this->employee) return collect();

        $crewIds = Crew::where('foreman_id', $this->employee->id)->pluck('id')
            ->merge(CrewMember::where('employee_id', $this->employee->id)->pluck('crew_id'))
            ->unique();

        return Job::whereIn('crew_id', $crewIds)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->with('property')
            ->orderBy('scheduled_date')
            ->get();
    }

    public function saveLog()
    {
        if (!$this->employee || !$this->selectedJobId || !$this->chemical_name) {
            session()->flash('error', 'Please fill in the required fields.');
            return;
        }

        $job = Job::find($this->selectedJobId);

        ChemicalLog::create([
            'employee_id' => $this->employee->id,
            'job_id' => $this->selectedJobId,
            'property_id' => $job?->property_id,
            'application_date' => now()->toDateString(),
            'chemical_name' => $this->chemical_name,
            'epa_registration_number' => $this->epa_registration_number,
            'target_pest' => $this->target_pest,
            'application_rate' => $this->application_rate,
            'application_unit' => $this->application_unit,
            'area_treated' => $this->area_treated,
            'wind_speed' => $this->wind_speed,
            'temperature' => $this->temperature,
            'notes' => $this->notes,
        ]);

        $this->reset('chemical_name', 'epa_registration_number', 'target_pest', 'application_rate', 'area_treated', 'wind_speed', 'temperature', 'notes', 'selectedJobId');
        $this->showForm = false;
        session()->flash('success', 'Chemical log saved!');
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-chemicals', [
            't' => $this->translations,
        ]);
    }
}
