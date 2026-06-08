<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service job. Related models are only included when eager loaded, so
 * the same resource serves both the job list and the job detail screen.
 *
 * @mixin \App\Models\Job
 */
class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?: 'Service Job',
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority ?: 'normal',
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'notes' => $this->notes,
            // Set by ScheduleController when the job belongs to a route.
            'route_order' => $this->route_order,
            'crew' => $this->whenLoaded('crew', fn () => $this->crew ? [
                'id' => $this->crew->id,
                'name' => $this->crew->name,
            ] : null),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer
                ? new CustomerResource($this->customer)
                : null),
            'property' => $this->whenLoaded('property', fn () => $this->property
                ? new PropertyResource($this->property)
                : null),
            'services' => $this->whenLoaded('jobServices', fn () => $this->jobServices->map(fn ($jobService) => [
                'id' => $jobService->id,
                'name' => $jobService->service?->name ?? 'Service',
                'description' => $jobService->description ?: $jobService->service?->description,
            ])->values()),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'media' => JobMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
