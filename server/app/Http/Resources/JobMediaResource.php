<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A file attached to a job (currently photos uploaded from the app).
 *
 * @mixin \App\Models\JobMedia
 */
class JobMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            // Request-host URL so the native app can load it directly.
            'url' => url('storage/' . $this->path),
            'original_name' => $this->original_name,
            'size' => (int) $this->size,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
