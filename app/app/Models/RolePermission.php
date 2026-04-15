<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'resource',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
