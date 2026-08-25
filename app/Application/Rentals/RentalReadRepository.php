<?php

namespace App\Application\Rentals;

interface RentalReadRepository
{
    public function list(array $filters): mixed;
    public function find(int $id): mixed;
    public function history(int $id): iterable;
    public function dashboard(string $today): array;
    public function availability(string $start, string $end): iterable;
    public function vehicles(): iterable;
    public function customers(): iterable;
    public function vehicleRateCents(int $vehicleId): int;
}
