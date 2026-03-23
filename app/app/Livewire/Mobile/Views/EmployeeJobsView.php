<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Employee;
use App\Models\Job;
use App\Models\Message;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeJobsView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $filter = 'all';
    public ?int $viewingJobId = null;
    public string $newNote = '';

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getJobsProperty()
    {
        if (!$this->employee) return collect();

        // Get crew IDs this employee belongs to (as foreman or member)
        $crewIds = Crew::where('foreman_id', $this->employee->id)->pluck('id')
            ->merge(CrewMember::where('employee_id', $this->employee->id)->pluck('crew_id'))
            ->unique();

        $query = Job::whereIn('crew_id', $crewIds)
            ->with(['property', 'customer', 'crew'])
            ->orderByDesc('scheduled_date');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query->get();
    }

    public function getViewingJobProperty(): ?Job
    {
        if (!$this->viewingJobId) return null;
        return Job::with(['property', 'customer', 'crew', 'messages', 'chemicalLogs', 'timeLogs'])->find($this->viewingJobId);
    }

    public function viewJob(int $id)
    {
        $this->viewingJobId = $id;
    }

    public function closeJob()
    {
        $this->viewingJobId = null;
        $this->newNote = '';
    }

    public function addNote()
    {
        if (!$this->viewingJobId || !$this->newNote || !$this->employee) return;

        Message::create([
            'sender_type' => Employee::class,
            'sender_id' => $this->employee->id,
            'job_id' => $this->viewingJobId,
            'body' => $this->newNote,
            'channel' => 'app',
        ]);

        $this->newNote = '';
    }

    public function updateJobStatus(int $jobId, string $status)
    {
        $job = Job::find($jobId);
        if ($job) {
            $data = ['status' => $status];
            if ($status === 'completed') {
                $data['completed_date'] = now();
            }
            $job->update($data);
        }
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-jobs', [
            't' => $this->translations,
        ]);
    }
}
