<?php

namespace App\Services\Translation;

/**
 * No-op driver: returns null for everything, so callers keep the original text.
 * This is the default until a real provider is configured (issue #56).
 */
class NullTranslationDriver implements TranslationDriver
{
    public function translateBatch(array $texts, string $source, string $target): array
    {
        return array_map(fn () => null, $texts);
    }
}
