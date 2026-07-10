<?php

namespace App\Http\Resources;

use App\Models\ChatMessage;
use App\Services\Translation\TranslationService;
use App\Support\AppLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ChatMessage
 */
class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Show the office's messages in the foreman's app language (issue #56).
        // The foreman's own messages are left as written.
        $body = $this->body;
        if ($body !== null && $this->sender === ChatMessage::SENDER_OFFICE) {
            $body = app(TranslationService::class)->translate($body, AppLocale::target($request));
        }

        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'sender_name' => $this->sender === ChatMessage::SENDER_OFFICE
                ? ($this->senderUser?->name ?? 'Office')
                : ($this->employee?->name ?? 'Foreman'),
            'body' => $body,
            'attachment' => $this->hasAttachment()
                ? [
                    'type' => $this->attachment_type,
                    'url' => $this->attachmentUrl(),
                    'name' => $this->attachment_name,
                ]
                : null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
