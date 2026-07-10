<?php

namespace App\Support;

use App\Models\ChatMessage;
use App\Models\Job;
use App\Services\Translation\TranslationService;

/**
 * Pre-translates every human-readable string in an API payload in a single
 * batched provider call, so the per-field lookups in the resources are all cache
 * hits (issue #56). A no-op when the target language needs no translation.
 */
class TranslationWarmer
{
    /**
     * @param  iterable<Job>  $jobs
     */
    public static function jobs(iterable $jobs, ?string $locale): void
    {
        $service = app(TranslationService::class);
        if (! $service->isTranslatable($locale)) {
            return;
        }

        $texts = [];
        foreach ($jobs as $job) {
            $texts[] = $job->title ?: 'Service Job';
            $texts[] = $job->description;
            $texts[] = $job->notes;

            if ($job->relationLoaded('jobServices')) {
                foreach ($job->jobServices as $jobService) {
                    $texts[] = $jobService->service?->name ?? 'Service';
                    $texts[] = $jobService->description ?: $jobService->service?->description;
                }
            }
        }

        $service->translateMany($texts, $locale);
    }

    /**
     * @param  iterable<ChatMessage>  $messages
     */
    public static function chat(iterable $messages, ?string $locale): void
    {
        $service = app(TranslationService::class);
        if (! $service->isTranslatable($locale)) {
            return;
        }

        $texts = [];
        foreach ($messages as $message) {
            // Only the office side is shown translated.
            if ($message->body !== null && $message->sender === ChatMessage::SENDER_OFFICE) {
                $texts[] = $message->body;
            }
        }

        $service->translateMany($texts, $locale);
    }
}
