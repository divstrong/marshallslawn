<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Translates via the Google Cloud Translation v2 REST API (API-key auth). The
 * endpoint accepts many `q` params per call, so a whole request's strings go in
 * one round-trip. Any failure returns nulls and the caller keeps the source text.
 */
class GoogleTranslationDriver implements TranslationDriver
{
    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function translateBatch(array $texts, string $source, string $target): array
    {
        $nulls = array_map(fn () => null, $texts);

        if (empty($this->apiKey) || $texts === []) {
            return $nulls;
        }

        $keys = array_keys($texts);
        $values = array_values($texts);

        try {
            $response = Http::timeout(12)->asForm()->post(
                'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($this->apiKey),
                [
                    'q' => $values,
                    'source' => substr($source, 0, 2),
                    'target' => substr($target, 0, 2),
                    'format' => 'text',
                ],
            );

            if (! $response->successful()) {
                Log::warning('translation.google.failed', ['status' => $response->status()]);

                return $nulls;
            }

            $translations = $response->json('data.translations');
            if (! is_array($translations) || count($translations) !== count($values)) {
                return $nulls;
            }

            $out = [];
            foreach ($keys as $i => $key) {
                $value = $translations[$i]['translatedText'] ?? null;
                $out[$key] = is_string($value) && $value !== '' ? $value : null;
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('translation.google.exception', ['error' => $e->getMessage()]);

            return $nulls;
        }
    }
}
