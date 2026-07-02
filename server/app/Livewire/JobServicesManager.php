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

    /** Working copy of the lines: ['id', 'service_id', 'service_name', 'price', 'description']. */
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
                'price' => number_format((float) $line->price, 2, '.', ''),
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
            ->get(['id', 'name', 'category', 'default_price']);
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

        $price = (float) ($service->default_price ?? 0);
        $line = JobService::create([
            'job_id' => $this->job->id,
            'service_id' => $service->id,
            'price' => $price,
            'description' => $service->name,
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->lines[] = [
            'id' => (int) $line->id,
            'service_id' => (int) $service->id,
            'service_name' => $service->name,
            'price' => number_format($price, 2, '.', ''),
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
            'price' => 0,
            'description' => '',
            'sort_order' => $this->nextSortOrder(),
        ]);

        $this->lines[] = [
            'id' => (int) $line->id,
            'service_id' => null,
            'service_name' => null,
            'price' => '0.00',
            'description' => '',
        ];
    }

    public function updatedLines($value, $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $row = $this->lines[(int) $index] ?? null;
        if (! $row || empty($row['id'])) {
            return;
        }

        if ($field === 'description') {
            JobService::where('id', $row['id'])->update(['description' => $row['description']]);
            return;
        }

        if ($field === 'price') {
            $price = round((float) ($row['price'] ?? 0), 2);
            $this->lines[(int) $index]['price'] = number_format($price, 2, '.', '');
            JobService::where('id', $row['id'])->update(['price' => $price]);
        }
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

    /** Live job total for the footer (sum of the line prices). */
    public function getJobTotalProperty(): string
    {
        $sum = 0;
        foreach ($this->lines as $line) {
            $sum += (float) ($line['price'] ?? 0);
        }

        return number_format($sum, 2, '.', '');
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
