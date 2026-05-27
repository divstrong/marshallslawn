<?php

namespace App\Livewire;

use App\Models\Job;
use App\Models\JobService;
use App\Models\Service;
use Livewire\Component;

class JobServicesManager extends Component
{
    public Job $job;

    public string $serviceSearch = '';
    public bool $showServiceDropdown = false;

    /** Working copy of the lines: ['id', 'service_id', 'service_name', 'description']. */
    public array $lines = [];

    public function mount(Job $job): void
    {
        $this->job = $job;
        $this->loadLines();
    }

    private function loadLines(): void
    {
        $this->lines = $this->job->jobServices()
            ->with('service:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (JobService $line) => [
                'id' => (int) $line->id,
                'service_id' => $line->service_id ? (int) $line->service_id : null,
                'service_name' => $line->service?->name,
                'description' => $line->description ?? '',
            ])
            ->all();
    }

    public function getServiceResultsProperty()
    {
        if (strlen($this->serviceSearch) < 1) {
            return collect();
        }

        return Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('full_name', 'like', "%{$this->serviceSearch}%")
                  ->orWhere('code', 'like', "%{$this->serviceSearch}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'category']);
    }

    public function updatedServiceSearch(): void
    {
        $this->showServiceDropdown = strlen($this->serviceSearch) >= 1;
    }

    public function addService(int $serviceId): void
    {
        $service = Service::find($serviceId);
        if (! $service) {
            return;
        }

        $line = JobService::create([
            'job_id' => $this->job->id,
            'service_id' => $service->id,
            'description' => $service->name,
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->lines[] = [
            'id' => (int) $line->id,
            'service_id' => (int) $service->id,
            'service_name' => $service->name,
            'description' => $service->name,
        ];

        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
    }

    public function addCustomLine(): void
    {
        $line = JobService::create([
            'job_id' => $this->job->id,
            'service_id' => null,
            'description' => '',
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->lines[] = [
            'id' => (int) $line->id,
            'service_id' => null,
            'service_name' => null,
            'description' => '',
        ];
    }

    public function updatedLines($value, $key): void
    {
        if (! str_ends_with($key, '.description')) {
            return;
        }

        [$index] = explode('.', $key);
        $row = $this->lines[(int) $index] ?? null;
        if (! $row || ! $row['id']) {
            return;
        }

        JobService::where('id', $row['id'])->update(['description' => $row['description']]);
    }

    public function removeLine(int $index): void
    {
        $row = $this->lines[$index] ?? null;
        if (! $row) {
            return;
        }

        if ($row['id']) {
            JobService::where('id', $row['id'])->delete();
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->job->jobServices()->max('sort_order')) + 1;
    }

    public function render()
    {
        return view('livewire.job-services-manager');
    }
}
