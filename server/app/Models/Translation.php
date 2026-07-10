<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A cached machine translation of one source phrase. Keyed by a hash of
 * (source locale, target locale, source text) so each unique phrase is
 * translated by the provider exactly once (issue #56).
 */
class Translation extends Model
{
    protected $fillable = [
        'hash',
        'source_locale',
        'target_locale',
        'source_text',
        'translated_text',
    ];

    public static function hashFor(string $source, string $target, string $text): string
    {
        return sha1($source . '|' . $target . '|' . $text);
    }
}
