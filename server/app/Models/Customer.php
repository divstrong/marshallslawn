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
        'password',
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
        'tags',
    ];

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
        ];
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
