<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'key',
        'name',
        'title',
        'body',
        'channel',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Placeholders an admin may use in a template, with a short description.
     * Shown as helper text on the resource form.
     *
     * @var array<string, string>
     */
    public const PLACEHOLDERS = [
        '{customer_name}' => "The customer's name",
        '{client_name}' => "Alias for {customer_name}",
        '{job_title}' => 'The job title',
        '{scheduled_date}' => "The job's scheduled date (e.g. Mon, Jun 22)",
        '{crew_name}' => 'The assigned crew',
        '{status}' => 'The job status (skipped / canceled)',
        '{address}' => 'The property address',
        '{city}' => 'The property city',
    ];

    /**
     * Substitute {placeholders} in a string from a vars map.
     *
     * @param  array<string, string|null>  $vars  Keys without braces, e.g. ['job_title' => '...'].
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
     * Render an active template by key into a title/body pair, or null if the
     * template is missing or disabled (callers fall back to default copy).
     *
     * @param  array<string, string|null>  $vars
     * @return array{title: string, body: string, channel: string}|null
     */
    public static function render(string $key, array $vars): ?array
    {
        $template = static::where('key', $key)->where('is_active', true)->first();
        if (! $template) {
            return null;
        }

        return [
            'title' => static::substitute($template->title, $vars),
            'body' => static::substitute($template->body, $vars),
            'channel' => $template->channel ?: 'alerts',
        ];
    }
}
