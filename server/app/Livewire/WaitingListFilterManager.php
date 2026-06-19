<?php

namespace App\Livewire;

use App\Models\Service;
use App\Models\WaitingListFilter;
use Livewire\Component;

class WaitingListFilterManager extends Component
{
    public array $filters = [];

    // Create/edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formLabel = '';
    public string $formServiceGroup = 'mowing';
    public bool $formIsActive = true;

    public function mount(): void
    {
        $this->loadFilters();
    }

    private function loadFilters(): void
    {
        $this->filters = WaitingListFilter::orderBy('sort_order')->orderBy('label')->get()->toArray();
    }

    public function groupOptions(): array
    {
        return Service::GROUPS;
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formLabel = '';
        $this->formServiceGroup = array_key_first(Service::GROUPS) ?: 'mowing';
        $this->formIsActive = true;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $filter = WaitingListFilter::find($id);
        if (! $filter) return;

        $this->editingId = $filter->id;
        $this->formLabel = $filter->label;
        $this->formServiceGroup = $filter->service_group;
        $this->formIsActive = $filter->is_active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function saveFilter(): void
    {
        $label = trim($this->formLabel);
        if ($label === '') return;

        $group = array_key_exists($this->formServiceGroup, Service::GROUPS)
            ? $this->formServiceGroup
            : array_key_first(Service::GROUPS);

        $data = [
            'label' => $label,
            'service_group' => $group,
            'is_active' => $this->formIsActive,
        ];

        if ($this->editingId) {
            WaitingListFilter::where('id', $this->editingId)->update($data);
        } else {
            $data['sort_order'] = (int) (WaitingListFilter::max('sort_order') ?? 0) + 1;
            WaitingListFilter::create($data);
        }

        $this->showForm = false;
        $this->loadFilters();
    }

    public function deleteFilter(int $id): void
    {
        WaitingListFilter::whereKey($id)->delete();
        $this->loadFilters();
    }

    public function render()
    {
        return view('livewire.waiting-list-filter-manager');
    }
}
