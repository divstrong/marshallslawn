<?php

namespace App\Support;

/**
 * Canonical "service mix" buckets used by the admin dashboard.
 *
 * Every job is grouped into one of four high-level service buckets — Spray,
 * Mulch, Mowing and Projects (anything that is none of the first three).
 *
 * Ideally a job is classified from the service group of its linked services
 * (see {@see \App\Models\Job::serviceGroup()}), but most historical / seeded
 * jobs have no linked service yet, so we fall back to keyword-matching the
 * job title + description. Once service tags are imported, that keyword
 * fallback can be retired in favour of the linked data — keep this class as
 * the single place that defines the buckets so the swap stays local.
 */
class ServiceGroup
{
    public const SPRAYING = 'spraying';

    public const MULCHING = 'mulching';

    public const MOWING = 'mowing';

    public const PROJECTS = 'projects';

    /**
     * Display labels for each bucket, in the order they appear on the chart.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        self::SPRAYING => 'Spray',
        self::MULCHING => 'Mulch',
        self::MOWING => 'Mowing',
        self::PROJECTS => 'Projects',
    ];

    /**
     * Chart colours for each bucket.
     *
     * @var array<string, string>
     */
    public const COLORS = [
        self::SPRAYING => '#3b82f6', // blue
        self::MULCHING => '#b45309', // brown
        self::MOWING => '#22c55e', // green
        self::PROJECTS => '#e00a35', // brand red
    ];

    /**
     * Keywords that map a free-text job title / description onto a bucket.
     * Evaluated in array order, so mulch / mow are matched before the broad
     * chemical-application keywords. Mirrors (and extends) the keyword
     * backfill used when seeding Service.service_group.
     *
     * @var array<string, list<string>>
     */
    private const KEYWORDS = [
        self::MULCHING => ['mulch'],
        self::MOWING => ['mow'],
        self::SPRAYING => [
            'spray', 'fert', 'weed', 'chemical', 'herbicide', 'pesticide',
            'pest', 'fungicide', 'insecticide', 'preemergent', 'pre-emergent',
            'pre emergent', 'treatment',
        ],
    ];

    /**
     * Normalise a stored Service.service_group value into one of our four
     * buckets. Anything that is not spray / mulch / mowing (including the
     * 'other' group and null) becomes a Project.
     */
    public static function fromServiceGroup(?string $group): string
    {
        return match ($group) {
            self::SPRAYING, 'spray' => self::SPRAYING,
            self::MULCHING, 'mulch' => self::MULCHING,
            self::MOWING, 'mow' => self::MOWING,
            default => self::PROJECTS,
        };
    }

    /**
     * Best-effort classification of free-text (a job title and/or
     * description) into a service bucket. Falls back to Projects when no
     * keyword matches.
     */
    public static function classify(?string $text): string
    {
        $haystack = strtolower(trim((string) $text));

        if ($haystack === '') {
            return self::PROJECTS;
        }

        foreach (self::KEYWORDS as $bucket => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $bucket;
                }
            }
        }

        return self::PROJECTS;
    }
}
