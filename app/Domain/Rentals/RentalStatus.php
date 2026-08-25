<?php

namespace App\Domain\Rentals;

enum RentalStatus: string
{
    case BOOKED = 'booked'; case ACTIVE = 'active'; case COMPLETED = 'completed'; case CANCELLED = 'cancelled';
}
