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
        'categories',
        'notes',
    ];

    protected $casts = [
        'categories' => 'array',
    ];

    public const CATEGORIES = [
        'mowing' => 'Mowing',
        'spray_techs' => 'Spray Techs',
        'projects' => 'Projects',
        'managers' => 'Managers',
    ];

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
