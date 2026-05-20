<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Employee;
use App\Models\Estimate;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeEstimatesView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $mode = 'list'; // 'list' | 'create' | 'edit'
    public ?int $viewingEstimateId = null;
    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function getEstimatesProperty()
    {
        if (! $this->employee) {
            return collect();
        }

        $query = Estimate::where('created_by', $this->employee->id)
            ->with(['customer', 'property'])
            ->orderByDesc('created_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->get();
    }

    public function getViewingEstimateProperty(): ?Estimate
    {
        if (! $this->viewingEstimateId) {
            return null;
        }

        return Estimate::with(['customer', 'property', 'lineItems'])->find($this->viewingEstimateId);
    }

    public function startCreate(): void
    {
        $this->viewingEstimateId = null;
        $this->mode = 'create';
    }

    public function viewEstimate(int $id): void
    {
        $this->viewingEstimateId = $id;
        $this->mode = 'edit';
    }

    public function backToList(): void
    {
        $this->mode = 'list';
        $this->viewingEstimateId = null;
    }

    #[On('estimate-saved')]
    public function handleEstimateSaved(int $estimateId): void
    {
        // Stay on the form so the user can continue editing/sharing.
        // The form switches from create -> edit context internally.
        $this->viewingEstimateId = $estimateId;
        if ($this->mode === 'create') {
            $this->mode = 'edit';
        }
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-estimates', [
            't' => $this->translations,
        ]);
    }
}
