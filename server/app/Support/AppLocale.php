<?php

namespace App\Support;

use App\Services\Translation\TranslationService;
use Illuminate\Http\Request;

/**
 * Resolves the target language the mobile app wants its content in (issue #56).
 * The Expo client sends its selected language in the `X-App-Language` header
 * (falling back to standard `Accept-Language`). English needs no translation.
 */
class AppLocale
{
    public static function target(Request $request): ?string
    {
        $header = $request->header('X-App-Language')
            ?: $request->header('Accept-Language', '');

        // Accept-Language can be "es-MX,es;q=0.9" — take the first tag's language.
        $lang = strtolower(substr(trim(explode(',', (string) $header)[0] ?? ''), 0, 2));

        return $lang !== '' ? $lang : null;
    }

    /** True when the request wants content in a language we translate into. */
    public static function wantsTranslation(Request $request): bool
    {
        return app(TranslationService::class)->isTranslatable(self::target($request));
    }
}
