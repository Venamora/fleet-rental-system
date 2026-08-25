<?php

namespace App\Application\Rentals;

use App\Domain\Rentals\DateRange;
use App\Domain\Rentals\Pricing;
use App\Domain\Shared\Contracts\Clock;

final class RentalQueryService
{
    public function __construct(private RentalReadRepository $repository, private Clock $clock) {}
    public function list(array $filters): mixed { return $this->repository->list($filters); }
    public function detail(int $id): mixed { return $this->repository->find($id); }
    public function history(int $id): iterable { return $this->repository->history($id); }
    public function dashboard(): array { return $this->repository->dashboard($this->clock->now()->format('Y-m-d')); }
    public function availability(string $start, string $end): iterable { $range = DateRange::fromStrings($start, $end); return $this->repository->availability($range->start->format('Y-m-d'), $range->end->format('Y-m-d')); }
    public function vehicles(): iterable { return $this->repository->vehicles(); }
    public function customers(): iterable { return $this->repository->customers(); }
    public function pricePreview(int $vehicleId, string $start, string $end): array
    {
        $range = DateRange::fromStrings($start, $end);
        $dailyRateCents = $this->repository->vehicleRateCents($vehicleId);
        return ['duration_days' => $range->durationDays(), 'daily_rate_cents' => $dailyRateCents] + Pricing::calculate($range->durationDays(), $dailyRateCents);
    }
}
