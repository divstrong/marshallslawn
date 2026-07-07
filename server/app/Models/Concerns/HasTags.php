<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Attach structured, category-managed Tags to a model (customers, jobs, …).
 *
 * Named `tagRecords` rather than `tags` so it doesn't collide with the
 * legacy free-text `tags` JSON column on Customer (used by marketing).
 */
trait HasTags
{
    public function tagRecords(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }
}
