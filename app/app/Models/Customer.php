<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

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
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'status',
        'customer_type',
        'account_number',
        'division',
        'map_code',
        'list_id',
        'source',
        'notes',
    ];

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
}
