<?php

namespace App\Livewire\SystemHealth;

use App\Services\SystemHealth\SmokeTestService;
use Livewire\Component;

class SmokePanel extends Component
{
    public array $results = [];
    public ?string $lastRunAt = null;
    public bool $running = false;

    public function run(): void
    {
        $this->running = true;
        $this->results = app(SmokeTestService::class)->runAll();
        $this->lastRunAt = now()->format('M j, Y g:i:s A');
        $this->running = false;
    }

    public function render()
    {
        return view('livewire.system-health.smoke-panel');
    }
}
