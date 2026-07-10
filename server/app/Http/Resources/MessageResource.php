<?php

namespace App\Http\Resources;

use App\Services\Translation\TranslationService;
use App\Support\AppLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Translated for non-English field staff (issue #56); cached per phrase.
            'body' => app(TranslationService::class)->translate($this->body, AppLocale::target($request)),
            'sender' => $this->sender_type ? class_basename($this->sender_type) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
