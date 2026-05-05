<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'is_admin',
        'can_see_routes',
        'can_see_chemicals',
        'can_see_estimates',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'can_see_routes' => 'boolean',
            'can_see_chemicals' => 'boolean',
            'can_see_estimates' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function hasAccessTo(string $resource): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->permissions()->where('resource', $resource)->exists();
    }
}
