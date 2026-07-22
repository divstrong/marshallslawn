<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A one-time sign-in code emailed to an employee. Rows are burned on use
 * (`consumed_at`) rather than deleted, so a replayed code fails closed.
 */
class EmployeeLoginCode extends Model
{
    protected $fillable = [
        'email',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed();
    }

    /**
     * The most recent unconsumed, unexpired code issued to an email.
     */
    public function scopeActiveFor(Builder $query, string $email): Builder
    {
        return $query
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest();
    }
}
