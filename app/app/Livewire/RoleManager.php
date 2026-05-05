<?php

namespace App\Livewire;

use App\Models\Role;
use Illuminate\Support\Str;
use Livewire\Component;

class RoleManager extends Component
{
    public array $roles = [];

    // Create/edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formLabel = '';
    public bool $formIsAdmin = false;
    public bool $formCanSeeRoutes = false;
    public bool $formCanSeeChemicals = false;
    public bool $formCanSeeEstimates = false;

    public function mount(): void
    {
        $this->loadRoles();
    }

    private function loadRoles(): void
    {
        $this->roles = Role::withCount('users')->orderBy('name')->get()->toArray();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formLabel = '';
        $this->formIsAdmin = false;
        $this->formCanSeeRoutes = false;
        $this->formCanSeeChemicals = false;
        $this->formCanSeeEstimates = false;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $role = Role::find($id);
        if (! $role) return;

        $this->editingId = $role->id;
        $this->formLabel = $role->label ?? $role->name;
        $this->formIsAdmin = $role->is_admin;
        $this->formCanSeeRoutes = $role->can_see_routes;
        $this->formCanSeeChemicals = $role->can_see_chemicals;
        $this->formCanSeeEstimates = $role->can_see_estimates;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function saveRole(): void
    {
        if (! $this->formLabel) return;

        $data = [
            'name' => Str::slug($this->formLabel, '_'),
            'label' => $this->formLabel,
            'is_admin' => $this->formIsAdmin,
            'can_see_routes' => $this->formCanSeeRoutes,
            'can_see_chemicals' => $this->formCanSeeChemicals,
            'can_see_estimates' => $this->formCanSeeEstimates,
        ];

        if ($this->editingId) {
            Role::where('id', $this->editingId)->update($data);
        } else {
            Role::create($data);
        }

        $this->showForm = false;
        $this->loadRoles();
    }

    public function deleteRole(int $id): void
    {
        $role = Role::find($id);
        if (! $role) return;

        // Don't delete if users are assigned
        if ($role->users()->count() > 0) {
            session()->flash('role-error', 'Cannot delete a role with assigned users.');
            return;
        }

        $role->delete();
        $this->loadRoles();
    }

    public function render()
    {
        return view('livewire.role-manager');
    }
}
