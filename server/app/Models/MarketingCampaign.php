<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Available HTML email templates (marketing campaign builder).
     *
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'announcement' => 'Announcement',
        'promotion' => 'Promotion',
        'newsletter' => 'Newsletter',
    ];

    /**
     * Templates currently offered in the campaign builder. Promotion and
     * Newsletter remain implemented but hidden for now; add them back here to
     * re-enable. Existing campaigns still render/label via TEMPLATES.
     *
     * @var array<string, string>
     */
    public const ACTIVE_TEMPLATES = [
        'announcement' => 'Announcement',
    ];

    protected $fillable = [
        'name',
        'subject',
        'template',
        'content',
        'recipient_tags',
        'recipient_customer_ids',
        'html_content',
        'status',
        'scheduled_at',
        'sent_at',
        'recipient_count',
        'open_count',
        'click_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'recipient_tags' => 'array',
            'recipient_customer_ids' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipient_count' => 'integer',
            'open_count' => 'integer',
            'click_count' => 'integer',
        ];
    }
}
