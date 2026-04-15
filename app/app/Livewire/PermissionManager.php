<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\RolePermission;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Component;

class PermissionManager extends Component
{
    public ?int $selectedRoleId = null;
    public array $permissions = [];
    public array $availableResources = [];

    public function mount(): void
    {
        $this->availableResources = $this->discoverResources();
    }

    private function discoverResources(): array
    {
        $resources = [];

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $shortName = class_basename($resource);
            $label = Str::of($shortName)->replaceLast('Resource', '')->headline()->toString();
            $resources[] = [
                'class' => $shortName,
                'label' => $label,
                'group' => $resource::getNavigationGroup() ?? 'Other',
            ];
        }

        usort($resources, fn ($a, $b) => $a['group'] <=> $b['group'] ?: $a['label'] <=> $b['label']);

        return $resources;
    }

    public function updatedSelectedRoleId(): void
    {
        $this->loadPermissions();
    }

    private function loadPermissions(): void
    {
        $this->permissions = [];

        if (! $this->selectedRoleId) return;

        $granted = RolePermission::where('role_id', $this->selectedRoleId)
            ->pluck('resource')
            ->toArray();

        foreach ($this->availableResources as $resource) {
            $this->permissions[$resource['class']] = in_array($resource['class'], $granted);
        }
    }

    public function togglePermission(string $resource): void
    {
        if (! $this->selectedRoleId) return;

        $role = Role::find($this->selectedRoleId);
        if (! $role || $role->is_admin) return;

        if ($this->permissions[$resource] ?? false) {
            // Remove
            RolePermission::where('role_id', $this->selectedRoleId)
                ->where('resource', $resource)
                ->delete();
            $this->permissions[$resource] = false;
        } else {
            // Add
            RolePermission::create([
                'role_id' => $this->selectedRoleId,
                'resource' => $resource,
            ]);
            $this->permissions[$resource] = true;
        }
    }

    public function getSelectedRoleProperty(): ?Role
    {
        return $this->selectedRoleId ? Role::find($this->selectedRoleId) : null;
    }

    public function render()
    {
        return view('livewire.permission-manager', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
