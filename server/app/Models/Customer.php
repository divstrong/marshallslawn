<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;
    use HasTags;
    use Notifiable;

    /**
     * Customers may ONLY reach the customer portal — never the admin panel.
     * (Admin uses the User model on the 'web' guard; this is a hard boundary.)
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'portal' && $this->status === 'active';
    }

    /**
     * Columns that hold a customer's name, searched token by token.
     *
     * @var list<string>
     */
    public const NAME_SEARCH_COLUMNS = ['first_name', 'last_name', 'company_name'];

    /**
     * Multi-token customer search.
     *
     * Every whitespace-separated token must match one of the name columns (AND
     * across tokens, OR across columns). That is what makes "Laura M" find
     * Laura Marshall: matching the whole term against each column separately —
     * the previous behaviour — can never span first and last name, because no
     * single column contains "Laura M".
     *
     * Email is matched against the *whole* term rather than per token. Matching
     * it per token makes short tokens catastrophically loose: a stray "M" would
     * otherwise match every address ending in ".com".
     *
     * Deliberately avoids CONCAT/CONCAT_WS so it behaves identically on MySQL
     * and the sqlite database the test suite runs against.
     */
    public function scopeSearchName(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $tokens = array_values(array_filter(preg_split('/\s+/', $term) ?: [], fn ($t): bool => $t !== ''));

        if ($tokens === []) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($tokens, $term): void {
            $outer->where(function (Builder $q) use ($tokens): void {
                foreach ($tokens as $token) {
                    $like = '%' . $token . '%';

                    $q->where(function (Builder $inner) use ($like): void {
                        foreach (self::NAME_SEARCH_COLUMNS as $column) {
                            $inner->orWhere($column, 'like', $like);
                        }
                    });
                }
            })->orWhere('email', 'like', '%' . $term . '%');
        });
    }

    /** Display name shown in the portal's user menu (Customer has no `name` column). */
    public function getFilamentName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))
            ?: ($this->company_name ?: ($this->email ?: 'Customer'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legacy_id',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'estimate_email',
        'billing_email',
        'service_email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'status',
        'customer_type',
        'scheduling_type',
        'account_number',
        'division',
        'map_code',
        'list_id',
        'source',
        'notes',
        'tags',
        'sms_consent_status',
        'sms_opt_in_sent_at',
        'sms_consent_at',
    ];

    /**
     * The address to use for one of the customer-facing streams, falling back to
     * the primary contact email when no dedicated one is on file.
     *
     * @param  'estimate'|'billing'|'service'  $stream
     */
    public function emailFor(string $stream): ?string
    {
        $dedicated = match ($stream) {
            'estimate' => $this->estimate_email,
            'billing' => $this->billing_email,
            'service' => $this->service_email,
            default => null,
        };

        return filled($dedicated) ? $dedicated : $this->email;
    }

    /** SMS consent states (A2P / CTIA). */
    public const SMS_PENDING = 'pending';
    public const SMS_CONFIRMED = 'confirmed';
    public const SMS_OPTED_OUT = 'opted_out';

    /**
     * @return array<string, string>
     */
    /**
     * The attributes hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'password' => 'hashed',
            'sms_opt_in_sent_at' => 'datetime',
            'sms_consent_at' => 'datetime',
        ];
    }

    /** True only when the customer has confirmed opt-in and has a phone to text. */
    public function canReceiveSms(): bool
    {
        return $this->sms_consent_status === self::SMS_CONFIRMED && filled($this->phone);
    }

    public function markSmsConfirmed(): void
    {
        $this->forceFill([
            'sms_consent_status' => self::SMS_CONFIRMED,
            'sms_consent_at' => now(),
        ])->saveQuietly();
    }

    public function markSmsOptedOut(): void
    {
        $this->forceFill([
            'sms_consent_status' => self::SMS_OPTED_OUT,
        ])->saveQuietly();
    }

    /**
     * Send the one-time double opt-in request, if double opt-in is enabled, the
     * customer has a phone, is still pending, and we haven't asked in the last 24h.
     */
    public function sendSmsOptInRequest(): void
    {
        if (! config('twilio.opt_in.enabled')) {
            return;
        }

        if (blank($this->phone) || $this->sms_consent_status !== self::SMS_PENDING) {
            return;
        }

        if ($this->sms_opt_in_sent_at && $this->sms_opt_in_sent_at->gt(now()->subDay())) {
            return;
        }

        $company = \App\Models\Setting::get('company_name', "Marshall's Lawn & Landscape");
        $first = $this->first_name ?: 'there';

        app(\App\Services\TwilioService::class)->sendSms(
            $this->phone,
            "{$company}: Hi {$first}, reply YES to receive text updates about your lawn & landscaping service. "
                . 'Msg frequency varies. Msg & data rates may apply. Reply HELP for help, STOP to cancel.',
            'opt_in_request',
        );

        $this->forceFill(['sms_opt_in_sent_at' => now()])->saveQuietly();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customerMessages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }
}
