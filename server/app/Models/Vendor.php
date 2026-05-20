<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'first_name',
        'last_name',
        'primary_contact',
        'secondary_contact',
        'job_title',
        'phone',
        'fax',
        'alt_phone',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'status',
        'notes',
    ];
}
