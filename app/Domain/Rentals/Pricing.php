<?php

namespace App\Domain\Rentals;

final class Pricing
{
    public static function calculate(int $durationDays, int $dailyRateCents): array
    {
        $subtotal = $durationDays * $dailyRateCents;
        if ($durationDays <= 7) return ['subtotal_cents' => $subtotal, 'discount_cents' => 0, 'total_cents' => $subtotal];
        $discount = intdiv($subtotal * 10, 100);
        $total = self::discountedTotal($subtotal);
        return ['subtotal_cents' => $subtotal, 'discount_cents' => $discount, 'total_cents' => $total];
    }

    public static function discountedTotal(int $subtotalCents): int { return intdiv($subtotalCents * 90 + 50, 100); }
}
