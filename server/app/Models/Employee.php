<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legacy_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'mobile_phone',
        'alt_phone',
        'address',
        'city',
        'state',
        'zip',
        'hire_date',
        'date_of_birth',
        'termination_date',
        'status',
        'role',
        'division',
        'list_id',
        'hourly_rate',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'date_of_birth' => 'date',
            'termination_date' => 'date',
            'hourly_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    public function crews(): HasMany
    {
        return $this->hasMany(Crew::class, 'foreman_id');
    }

    public function crewMemberships(): HasMany
    {
        return $this->hasMany(CrewMember::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function chemicalLogs(): HasMany
    {
        return $this->hasMany(ChemicalLog::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(EmployeeLocation::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    /** Most recent GPS ping — used for the live foreman pin on Dispatch. */
    public function latestLocation(): HasOne
    {
        return $this->hasOne(EmployeeLocation::class)->latestOfMany('recorded_at');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    /**
     * IDs of every crew this employee works on — either as the crew's
     * foreman or as a listed crew member.
     *
     * @return Collection<int, int>
     */
    public function crewIds(): Collection
    {
        return Crew::query()
            ->where('foreman_id', $this->id)
            ->pluck('id')
            ->merge(CrewMember::query()->where('employee_id', $this->id)->pluck('crew_id'))
            ->unique()
            ->values();
    }

    /**
     * IDs of crews this employee is the foreman of.
     *
     * @return Collection<int, int>
     */
    public function foremanCrewIds(): Collection
    {
        return Crew::query()->where('foreman_id', $this->id)->pluck('id');
    }
}
