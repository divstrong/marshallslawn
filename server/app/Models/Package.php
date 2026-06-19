<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('quantity')->withTimestamps();
    }

    /** Other packages bundled inside this one (issue #20). */
    public function subPackages(): BelongsToMany
    {
        return $this->belongsToMany(
            Package::class,
            'package_sub_package',
            'parent_package_id',
            'child_package_id',
        )->withPivot('quantity')->withTimestamps();
    }

    /** Packages that bundle this one. */
    public function parentPackages(): BelongsToMany
    {
        return $this->belongsToMany(
            Package::class,
            'package_sub_package',
            'child_package_id',
            'parent_package_id',
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * IDs of packages that may NOT be nested under this one because doing so
     * would create a cycle (this package itself, plus anything that already
     * contains it, transitively).
     *
     * @return array<int, int>
     */
    public function disallowedSubPackageIds(): array
    {
        $blocked = [$this->id];
        $stack = [$this->id];

        // Walk up the parent chain so a child can't bundle an ancestor.
        while ($stack) {
            $current = array_pop($stack);
            $parents = Package::query()
                ->whereHas('subPackages', fn ($q) => $q->where('child_package_id', $current))
                ->pluck('id')
                ->all();

            foreach ($parents as $pid) {
                if (! in_array($pid, $blocked, true)) {
                    $blocked[] = $pid;
                    $stack[] = $pid;
                }
            }
        }

        return $blocked;
    }
}
