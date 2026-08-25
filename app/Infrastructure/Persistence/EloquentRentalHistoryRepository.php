<?php

namespace App\Infrastructure\Persistence;

use App\Application\Rentals\RentalHistoryRepository;
use App\Models\RentalHistoryEvent;
use App\Domain\Shared\Contracts\Clock;

final class EloquentRentalHistoryRepository implements RentalHistoryRepository
{
    public function __construct(private Clock $clock) {}
    public function append(int $rentalId, string $type, string $state, ?string $reason = null, ?string $effectiveEnd = null): void
    {
        RentalHistoryEvent::create(['rental_id'=>$rentalId,'event_type'=>$type,'occurred_at'=>$this->clock->now(),'state'=>$state,'reason'=>$reason,'effective_end_date'=>$effectiveEnd]);
    }
}
