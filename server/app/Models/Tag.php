<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_category_id',
        'name',
        'is_automation',
        'source_modified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_automation' => 'boolean',
            'source_modified_at' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class, 'tag_category_id');
    }

    public function customers(): MorphToMany
    {
        return $this->morphedByMany(Customer::class, 'taggable');
    }

    public function jobs(): MorphToMany
    {
        return $this->morphedByMany(Job::class, 'taggable');
    }
}
