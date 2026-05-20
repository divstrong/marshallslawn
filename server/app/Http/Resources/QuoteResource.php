<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An estimate, surfaced to the Estimator role as a "quote".
 *
 * @mixin \App\Models\Estimate
 */
class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estimate_number' => $this->estimate_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'square_footage' => $this->square_footage !== null ? (float) $this->square_footage : null,
            'valid_until' => $this->valid_until?->toDateString(),
            'notes' => $this->notes,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_human' => $this->created_at?->diffForHumans(),
            'public_url' => $this->getPublicUrl(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer
                ? new CustomerResource($this->customer)
                : null),
            'property' => $this->whenLoaded('property', fn () => $this->property
                ? new PropertyResource($this->property)
                : null),
            'line_items' => QuoteLineItemResource::collection($this->whenLoaded('lineItems')),
        ];
    }
}
