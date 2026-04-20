<?php

namespace App\Livewire\SystemHealth;

use App\Services\SystemHealth\HealthCheckService;
use Livewire\Component;

class EnvironmentPanel extends Component
{
    public array $checks = [];
    public array $schemaChecks = [];
    public ?string $lastRunAt = null;

    public function mount(): void
    {
        $this->run();
    }

    public function run(): void
    {
        $service = app(HealthCheckService::class);
        $this->checks = $service->runEnvironmentChecks();
        $this->schemaChecks = $service->runSchemaChecks();
        $this->lastRunAt = now()->format('M j, Y g:i:s A');
    }

    public function render()
    {
        return view('livewire.system-health.environment-panel');
    }
}
