<?php

namespace App\Http\Resources;

use App\Services\Translation\TranslationService;
use App\Support\AppLocale;
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
        // Translate admin-authored, human-readable fields for non-English field
        // staff (issue #56). Controllers pre-warm the cache, so these resolve
        // from cache rather than calling the provider per field.
        $locale = AppLocale::target($request);
        $tr = fn (?string $text): ?string => app(TranslationService::class)->translate($text, $locale);

        return [
            'id' => $this->id,
            'title' => $tr($this->title ?: 'Service Job'),
            'description' => $tr($this->description),
            'status' => $this->status,
            'priority' => $this->priority ?: 'normal',
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_date' => $this->completed_date?->toDateString(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'notes' => $tr($this->notes),
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
                'name' => $tr($jobService->service?->name ?? 'Service'),
                'description' => $tr($jobService->description ?: $jobService->service?->description),
                'completed' => $jobService->completed_at !== null,
                'completed_at' => $jobService->completed_at?->toIso8601String(),
            ])->values()),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'media' => JobMediaResource::collection($this->whenLoaded('media')),
            // Set by JobController@show for spray jobs near a mowing visit (issue #12).
            'mowing_conflict' => $this->mowing_conflict ?? null,
        ];
    }
}
