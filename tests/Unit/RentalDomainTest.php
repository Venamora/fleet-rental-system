<?php

namespace Tests\Unit;

use App\Domain\Rentals\DateRange;
use App\Domain\Rentals\Pricing;
use PHPUnit\Framework\TestCase;

class RentalDomainTest extends TestCase
{
    public function test_dates_are_inclusive_and_same_day_is_one(): void
    {
        self::assertSame(1, DateRange::fromStrings('2026-08-24', '2026-08-24')->durationDays());
        self::assertSame(8, DateRange::fromStrings('2026-08-24', '2026-08-31')->durationDays());
    }

    public function test_boundary_ranges_overlap(): void
    {
        $left = DateRange::fromStrings('2026-08-10', '2026-08-12');
        $right = DateRange::fromStrings('2026-08-12', '2026-08-15');
        self::assertTrue($left->overlaps($right));
    }

    public function test_discount_uses_half_up_integer_rounding(): void
    {
        self::assertSame(9001, Pricing::discountedTotal(10001));
        self::assertSame(70000, Pricing::calculate(7, 10000)['total_cents']);
    }

    public function test_two_day_breakdown_preserves_authoritative_cent_values(): void
    {
        $range = DateRange::fromStrings('2026-08-26', '2026-08-27');
        self::assertSame(2, $range->durationDays());
        self::assertSame(['subtotal_cents' => 20002, 'discount_cents' => 0, 'total_cents' => 20002], Pricing::calculate($range->durationDays(), 10001));
        self::assertSame(9001, Pricing::discountedTotal(10001));
    }
}
