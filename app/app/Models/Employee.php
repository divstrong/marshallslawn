<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'hire_date',
        'termination_date',
        'status',
        'hourly_rate',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
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
            'termination_date' => 'date',
            'hourly_rate' => 'decimal:2',
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
}
