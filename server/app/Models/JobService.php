<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobService extends Model
{
    protected $fillable = [
        'job_id',
        'service_id',
        'quantity',
        'unit_price',
        'price',
        'description',
        'sort_order',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
