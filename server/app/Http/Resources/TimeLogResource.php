<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A clock-in/clock-out shift. `duration_minutes` reflects elapsed time
 * (using "now" for an open shift) minus any break time.
 *
 * @mixin \App\Models\TimeLog
 */
class TimeLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $end = $this->clock_out ?? now();
        $minutes = $this->clock_in
            ? max(0, $this->clock_in->diffInMinutes($end) - (int) $this->break_minutes)
            : 0;

        return [
            'id' => $this->id,
            'clock_in' => $this->clock_in?->toIso8601String(),
            'clock_out' => $this->clock_out?->toIso8601String(),
            'break_minutes' => (int) $this->break_minutes,
            'status' => $this->status,
            'is_active' => $this->clock_out === null,
            'duration_minutes' => (int) $minutes,
        ];
    }
}
