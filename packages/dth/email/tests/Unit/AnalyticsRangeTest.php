<?php

namespace Dth\Email\Tests\Unit;

use Carbon\CarbonImmutable;
use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\Tests\TestCase;
use InvalidArgumentException;

class AnalyticsRangeTest extends TestCase
{
    public function test_last_days_builds_inclusive_range_and_equal_previous_period(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 10:00:00');

        $range = AnalyticsRange::lastDays(7);

        $this->assertSame('2026-09-04 00:00:00', $range->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-10 23:59:59', $range->end->format('Y-m-d H:i:s'));
        $this->assertSame(7, $range->days());

        $previous = $range->previous();

        $this->assertNotNull($previous);
        $this->assertSame('2026-08-28 00:00:00', $previous->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-03 23:59:59', $previous->end->format('Y-m-d H:i:s'));
        $this->assertSame(7, $previous->days());

        CarbonImmutable::setTestNow();
    }

    public function test_invalid_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AnalyticsRange('2026-09-11', '2026-09-10');
    }
}
