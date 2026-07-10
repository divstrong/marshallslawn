<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Translates via the OpenAI Chat Completions API (the key already in .env).
 * All strings in a request are translated in a single call: the model is asked
 * to return a JSON array aligned to the input, so a whole job list costs one
 * round-trip. Any failure returns nulls and the caller keeps the English text.
 */
class OpenAiTranslationDriver implements TranslationDriver
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {
    }

    public function translateBatch(array $texts, string $source, string $target): array
    {
        $nulls = array_map(fn () => null, $texts);

        if (empty($this->apiKey) || $texts === []) {
            return $nulls;
        }

        // Work over a positional list; remember the original keys to restore them.
        $keys = array_keys($texts);
        $values = array_values($texts);

        $languageName = $this->languageName($target);

        $system = "You are a translation engine for a lawn care and landscaping company's field-staff app. "
            . "Translate each string from {$this->languageName($source)} to {$languageName}. "
            . 'Keep it natural and concise for a work crew. Preserve names, addresses, numbers, and units. '
            . 'Return ONLY a JSON object {"items": [...]} whose items array has exactly one translated string per '
            . 'input string, in the same order. Do not add or drop items.';

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(12)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => json_encode(['items' => $values], JSON_UNESCAPED_UNICODE)],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('translation.openai.failed', ['status' => $response->status()]);

                return $nulls;
            }

            $content = $response->json('choices.0.message.content');
            $decoded = json_decode((string) $content, true);
            $items = $decoded['items'] ?? null;

            if (! is_array($items) || count($items) !== count($values)) {
                Log::warning('translation.openai.shape_mismatch', [
                    'expected' => count($values),
                    'got' => is_array($items) ? count($items) : null,
                ]);

                return $nulls;
            }

            // Re-key the positional results back onto the caller's keys.
            $out = [];
            foreach ($keys as $i => $key) {
                $value = $items[$i] ?? null;
                $out[$key] = is_string($value) && $value !== '' ? $value : null;
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('translation.openai.exception', ['error' => $e->getMessage()]);

            return $nulls;
        }
    }

    private function languageName(string $locale): string
    {
        return match (substr($locale, 0, 2)) {
            'es' => 'Spanish',
            'en' => 'English',
            'fr' => 'French',
            'pt' => 'Portuguese',
            default => $locale,
        };
    }
}
