<?php

namespace App\Livewire;

use App\Models\Crew;
use App\Models\CrewMember;
use App\Models\Employee;
use Livewire\Component;

class CrewMembersManager extends Component
{
    public Crew $crew;

    public string $search = '';
    public bool $showAddModal = false;
    public ?int $editingMemberId = null;

    public function mount(Crew $crew): void
    {
        $this->crew = $crew;
    }

    public function getFilteredEmployeesProperty()
    {
        if (! $this->showAddModal || strlen($this->search) < 1) {
            return collect();
        }

        $existingIds = $this->crew->members()->pluck('employee_id');

        return Employee::where('status', 'active')
            ->whereNotIn('id', $existingIds)
            ->where('id', '!=', $this->crew->foreman_id)
            ->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                  ->orWhere('last_name', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%");
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    public function getMembersProperty()
    {
        return $this->crew->members()->with('employee')->get();
    }

    public function addMember(int $employeeId): void
    {
        if ($this->crew->members()->where('employee_id', $employeeId)->exists()) {
            return;
        }

        CrewMember::create([
            'crew_id' => $this->crew->id,
            'employee_id' => $employeeId,
        ]);

        $this->search = '';
        $this->showAddModal = false;
        $this->crew->refresh();
    }

    public function removeMember(int $memberId): void
    {
        CrewMember::where('id', $memberId)
            ->where('crew_id', $this->crew->id)
            ->delete();

        $this->crew->refresh();
    }

    public function openAddModal(): void
    {
        $this->showAddModal = true;
        $this->search = '';
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->search = '';
    }

    public function render()
    {
        return view('livewire.crew-members-manager');
    }
}
