<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An office-editable SMS body for one customer notification event. Managed under
 * Settings → Notifications; each has a {placeholder} vocabulary and an on/off
 * toggle, so copy and activation are controlled without a deploy.
 */
class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Placeholders an admin may use, with a short description. {name} is the
     * customer's first name (the one the office specifically asked for).
     *
     * @var array<string, string>
     */
    public const PLACEHOLDERS = [
        '{name}' => "The customer's first name",
        '{company}' => 'Your company name',
        '{service}' => 'The job title / service',
        '{date}' => "The job's scheduled date (e.g. Mon, Jul 13)",
        '{status}' => 'For reschedules: "rescheduled" or "canceled"',
        '{invoice_number}' => 'The invoice number',
        '{amount}' => 'The invoice total',
        '{link}' => 'A public link to the invoice',
    ];

    /**
     * Stand-in values for previewing and test-sending a template. They match the
     * sample messages registered with the A2P campaign (see
     * docs/a2p-campaign-registration.md), so what an admin previews is what a
     * carrier reviewer was shown.
     *
     * @return array<string, string>
     */
    public static function sampleVars(): array
    {
        return [
            'name' => 'Jane',
            'company' => Setting::get('company_name', "Marshall's Lawn & Landscape"),
            'service' => 'Weekly Mow',
            'date' => 'Mon, Jul 13',
            'status' => 'rescheduled',
            'invoice_number' => 'INV-00042',
            'amount' => '$250.00',
            'link' => url('/invoice/abc123'),
        ];
    }

    /**
     * Character count and billable segment count for a body. Carriers bill per
     * segment, and one stray non-ASCII character — a curly quote pasted out of
     * Word, an emoji — drops the segment size from 160 to 70 and doubles the bill
     * without changing how the message looks. Surfacing that is the point.
     *
     * Deliberately conservative: any non-ASCII is treated as UCS-2, so a few
     * characters GSM-7 could actually carry (£, €, accents) are counted as if they
     * could not. Over-estimating a segment count is a harmless warning;
     * under-estimating it is a surprise on the invoice.
     *
     * @return array{chars: int, segments: int, encoding: string}
     */
    public static function segmentInfo(string $text): array
    {
        $chars = mb_strlen($text);
        $isGsm = (bool) preg_match('/^[\x0A\x0D\x20-\x7E]*$/', $text);

        if ($isGsm) {
            // These are single characters but cost two septets each in GSM-7.
            $units = strlen($text) + preg_match_all('/[\^{}\\\[~\]|]/', $text);
            $single = 160;
            $multi = 153;
        } else {
            $units = $chars;
            $single = 70;
            $multi = 67;
        }

        $segments = $units === 0
            ? 0
            : ($units <= $single ? 1 : (int) ceil($units / $multi));

        return ['chars' => $chars, 'segments' => $segments, 'encoding' => $isGsm ? 'GSM-7' : 'UCS-2'];
    }

    /**
     * Substitute {placeholders} from a vars map (keys without braces).
     *
     * @param  array<string, string|null>  $vars
     */
    public static function substitute(string $text, array $vars): string
    {
        $replacements = [];
        foreach ($vars as $key => $value) {
            $replacements['{' . $key . '}'] = (string) ($value ?? '');
        }

        return strtr($text, $replacements);
    }

    /**
     * Render an active template by key into a message body, or null when the
     * template is missing or disabled (so callers simply send nothing).
     *
     * @param  array<string, string|null>  $vars
     */
    public static function render(string $key, array $vars): ?string
    {
        $template = static::where('key', $key)->where('is_active', true)->first();
        if (! $template) {
            return null;
        }

        return static::substitute($template->body, $vars);
    }
}
