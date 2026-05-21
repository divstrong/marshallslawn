<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An Expo push token registered by a foreman's device.
 */
class PushToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'token',
        'platform',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
