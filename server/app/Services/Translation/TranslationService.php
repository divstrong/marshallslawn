<?php

namespace App\Services\Translation;

use App\Models\Translation;

/**
 * Translates admin-authored content (job details, chat) for field staff whose
 * app is set to another language (issue #56).
 *
 * Every unique source phrase is translated by the configured provider exactly
 * once and cached in the `translations` table, so repeat requests are instant
 * and cost nothing. The provider is only asked about phrases not already cached,
 * batched into a single call. Anything the provider can't translate falls back
 * to the original text — translation never breaks or blocks an API response.
 *
 * An LLM is not required: the driver is pluggable (OpenAI / Google / null), so
 * a cheaper dedicated translation service can be swapped in via config without
 * touching callers.
 */
class TranslationService
{
    public const DEFAULT_SOURCE = 'en';

    public function __construct(private readonly TranslationDriver $driver)
    {
    }

    /** Languages other than this that we actually translate into. */
    public function isTranslatable(?string $target): bool
    {
        $target = $this->normalize($target);

        return $target !== '' && $target !== self::DEFAULT_SOURCE;
    }

    /** Translate a single string, returning the original on any miss. */
    public function translate(?string $text, ?string $target, string $source = self::DEFAULT_SOURCE): ?string
    {
        if ($text === null || trim($text) === '' || ! $this->isTranslatable($target)) {
            return $text;
        }

        return $this->translateMany([$text], $target, $source)[$text] ?? $text;
    }

    /**
     * Translate many strings at once. Returns a map of original => translated
     * (original preserved for anything not translated). Cached phrases skip the
     * provider entirely; only the misses are sent, in one batched call.
     *
     * @param  array<int, string|null>  $texts
     * @return array<string, string>
     */
    public function translateMany(array $texts, ?string $target, string $source = self::DEFAULT_SOURCE): array
    {
        $target = $this->normalize($target);
        $source = $this->normalize($source);

        // Unique, non-empty source strings.
        $unique = [];
        foreach ($texts as $text) {
            if (is_string($text) && trim($text) !== '') {
                $unique[$text] = $text;
            }
        }

        if ($unique === [] || ! $this->isTranslatable($target)) {
            return $unique;
        }

        $result = [];
        $missing = [];
        foreach ($unique as $text) {
            $hash = Translation::hashFor($source, $target, $text);
            $cached = Translation::where('hash', $hash)->value('translated_text');
            if ($cached !== null) {
                $result[$text] = $cached;
            } else {
                $missing[$hash] = $text;
            }
        }

        if ($missing !== []) {
            $translated = $this->driver->translateBatch(array_values($missing), $source, $target);
            $translated = array_values($translated);

            $i = 0;
            foreach ($missing as $hash => $text) {
                $value = $translated[$i] ?? null;
                $i++;

                if (is_string($value) && $value !== '') {
                    Translation::updateOrCreate(
                        ['hash' => $hash],
                        [
                            'source_locale' => $source,
                            'target_locale' => $target,
                            'source_text' => $text,
                            'translated_text' => $value,
                        ],
                    );
                    $result[$text] = $value;
                } else {
                    // Provider miss: keep the original so the response is never broken.
                    $result[$text] = $text;
                }
            }
        }

        return $result;
    }

    private function normalize(?string $locale): string
    {
        return strtolower(substr(trim((string) $locale), 0, 2));
    }
}
