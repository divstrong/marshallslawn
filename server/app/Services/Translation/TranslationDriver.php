<?php

namespace App\Services\Translation;

interface TranslationDriver
{
    /**
     * Translate a batch of strings. Returns an array aligned to $texts by key;
     * a value is null when that string could not be translated (the caller then
     * falls back to the original text). Implementations must never throw.
     *
     * @param  array<int|string, string>  $texts
     * @return array<int|string, string|null>
     */
    public function translateBatch(array $texts, string $source, string $target): array;
}
