<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

trait ChecksResourceAccess
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasAccessTo(class_basename(static::class));
    }
}
