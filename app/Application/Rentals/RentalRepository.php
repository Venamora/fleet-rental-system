<?php

namespace App\Application\Rentals;

interface RentalRepository
{
    public function blockingOverlap(int $vehicleId, string $start, string $end, ?int $exceptId = null): bool;
    public function create(array $data): mixed;
    public function find(int $id): mixed;
    public function update(int $id, array $data): void;
    public function lockVehicle(int $vehicleId): void;
    public function forVehicle(int $vehicleId, string $start, string $end): iterable;
    public function dueForCompletion(string $date): iterable;
    public function dueForActivation(string $date): iterable;
}
