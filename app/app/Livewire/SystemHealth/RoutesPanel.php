<?php

namespace App\Livewire\SystemHealth;

use App\Services\SystemHealth\HealthCheckService;
use Livewire\Component;

class RoutesPanel extends Component
{
    public array $checks = [];
    public ?string $lastRunAt = null;

    public function mount(): void
    {
        $this->run();
    }

    public function run(): void
    {
        $this->checks = app(HealthCheckService::class)->runRouteChecks();
        $this->lastRunAt = now()->format('M j, Y g:i:s A');
    }

    public function render()
    {
        return view('livewire.system-health.routes-panel');
    }
}
