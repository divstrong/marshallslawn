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
