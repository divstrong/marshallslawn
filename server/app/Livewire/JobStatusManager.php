<?php

namespace App\Livewire;

use App\Models\Job;
use App\Models\JobStatus;
use Illuminate\Support\Str;
use Livewire\Component;

class JobStatusManager extends Component
{
    public array $statuses = [];

    // Create/edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formLabel = '';
    public string $formColor = 'gray';

    public function mount(): void
    {
        $this->loadStatuses();
    }

    private function loadStatuses(): void
    {
        $statuses = JobStatus::orderBy('sort_order')->orderBy('label')->get();

        // Count jobs per status so the UI can block deleting one that's in use.
        $counts = Job::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $this->statuses = $statuses->map(fn (JobStatus $s) => array_merge($s->toArray(), [
            'jobs_count' => (int) ($counts[$s->name] ?? 0),
        ]))->all();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formLabel = '';
        $this->formColor = 'gray';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $status = JobStatus::find($id);
        if (! $status) return;

        $this->editingId = $status->id;
        $this->formLabel = $status->label;
        $this->formColor = $status->color;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function saveStatus(): void
    {
        $label = trim($this->formLabel);
        if ($label === '') return;

        $color = array_key_exists($this->formColor, JobStatus::COLORS) ? $this->formColor : 'gray';

        if ($this->editingId) {
            $status = JobStatus::find($this->editingId);
            if (! $status) return;

            // Protected statuses keep their machine name (logic depends on it);
            // only the label/color are editable.
            $data = ['label' => $label, 'color' => $color];
            if (! $status->is_protected) {
                $data['name'] = $this->uniqueName($label, $status->id);
            }
            $status->update($data);
        } else {
            JobStatus::create([
                'name' => $this->uniqueName($label),
                'label' => $label,
                'color' => $color,
                'sort_order' => (int) (JobStatus::max('sort_order') ?? 0) + 1,
                'is_protected' => false,
            ]);
        }

        $this->showForm = false;
        $this->loadStatuses();
    }

    public function deleteStatus(int $id): void
    {
        $status = JobStatus::find($id);
        if (! $status) return;

        if ($status->is_protected) {
            session()->flash('job-status-error', 'Core statuses cannot be deleted.');
            return;
        }

        if (Job::where('status', $status->name)->exists()) {
            session()->flash('job-status-error', 'Cannot delete a status that is assigned to jobs.');
            return;
        }

        $status->delete();
        $this->loadStatuses();
    }

    private function uniqueName(string $label, ?int $ignoreId = null): string
    {
        $base = Str::slug($label, '_') ?: 'status';
        $name = $base;
        $i = 2;
        while (JobStatus::where('name', $name)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $name = $base . '_' . $i++;
        }

        return $name;
    }

    public function render()
    {
        return view('livewire.job-status-manager');
    }
}
