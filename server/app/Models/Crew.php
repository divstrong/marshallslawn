<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crew extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'foreman_id',
        'status',
        'division',
        'type',
        'notes',
    ];

    protected $casts = [
        'type' => 'array',
    ];

    /**
     * Crew type options, editable from Settings -> Crew Types.
     *
     * Replaces the old hardcoded CATEGORIES const. `type` stays a JSON array —
     * a crew can handle more than one — so filters use whereJsonContains.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return CrewType::options();
    }

    /** Human labels for this crew's types, in the configured order. */
    public function typeLabels(): array
    {
        $options = static::typeOptions();

        return array_values(array_intersect_key($options, array_flip($this->type ?? [])));
    }

    public function foreman(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'foreman_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CrewMember::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }
}
