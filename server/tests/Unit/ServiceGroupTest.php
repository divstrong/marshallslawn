<?php

namespace Tests\Unit;

use App\Support\ServiceGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServiceGroupTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function titleProvider(): array
    {
        return [
            'mowing title' => ['Weekly Mowing', ServiceGroup::MOWING],
            'mulch title' => ['Blown Compost Mulch', ServiceGroup::MULCHING],
            'spray title' => ['Chemical Spraying', ServiceGroup::SPRAYING],
            'fertilizer is spray' => ['GL6 Late Fall Fertlizer', ServiceGroup::SPRAYING],
            'weed control is spray' => ['GL7 Winter Fert / Weed Ctrl', ServiceGroup::SPRAYING],
            'preemergent is spray' => ['August Preemergent Poa', ServiceGroup::SPRAYING],
            'fungicide is spray' => ['Fungicide Application', ServiceGroup::SPRAYING],
            'hedge is project' => ['Hedge Trimming - Fall', ServiceGroup::PROJECTS],
            'aeration is project' => ['Core Aeration & Seeding', ServiceGroup::PROJECTS],
            'leaf is project' => ['Curbside Leaf Collection', ServiceGroup::PROJECTS],
            'empty is project' => ['', ServiceGroup::PROJECTS],
        ];
    }

    #[DataProvider('titleProvider')]
    public function test_classify_buckets_titles(string $title, string $expected): void
    {
        $this->assertSame($expected, ServiceGroup::classify($title));
    }

    public function test_classify_handles_null(): void
    {
        $this->assertSame(ServiceGroup::PROJECTS, ServiceGroup::classify(null));
    }

    public function test_mulch_takes_priority_over_spray_keywords(): void
    {
        // Mulch is matched before the broad spray keywords.
        $this->assertSame(ServiceGroup::MULCHING, ServiceGroup::classify('Mulch and weed bed'));
    }

    public function test_from_service_group_normalises_stored_values(): void
    {
        $this->assertSame(ServiceGroup::SPRAYING, ServiceGroup::fromServiceGroup('spraying'));
        $this->assertSame(ServiceGroup::MULCHING, ServiceGroup::fromServiceGroup('mulching'));
        $this->assertSame(ServiceGroup::MOWING, ServiceGroup::fromServiceGroup('mowing'));
        $this->assertSame(ServiceGroup::PROJECTS, ServiceGroup::fromServiceGroup('other'));
        $this->assertSame(ServiceGroup::PROJECTS, ServiceGroup::fromServiceGroup(null));
    }
}
