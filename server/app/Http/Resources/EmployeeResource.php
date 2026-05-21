<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated employee plus the capability flags that drive which
 * tabs and views the native app exposes.
 *
 * @mixin \App\Models\Employee
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = Role::query()->where('name', $this->role)->first();

        return [
            'id' => $this->id,
            'name' => $this->name ?: trim("{$this->first_name} {$this->last_name}"),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->mobile_phone ?: $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'role' => $this->role,
            'role_label' => $role?->label ?? ucfirst((string) $this->role),
            'division' => $this->division,
            'avatar_url' => $this->avatar_path ? url('storage/' . $this->avatar_path) : null,
            'capabilities' => [
                'can_see_routes' => (bool) $role?->can_see_routes,
                'can_see_chemicals' => (bool) $role?->can_see_chemicals,
                'can_see_estimates' => (bool) $role?->can_see_estimates,
            ],
        ];
    }
}
