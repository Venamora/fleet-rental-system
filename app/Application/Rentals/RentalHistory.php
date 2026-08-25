<?php

namespace App\Application\Rentals;

interface RentalHistory
{
    public function append(int $rentalId, string $type, string $state, ?string $reason = null, ?string $effectiveEnd = null): void;
}
