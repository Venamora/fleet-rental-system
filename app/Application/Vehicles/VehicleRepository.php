<?php
namespace App\Application\Vehicles;

interface VehicleRepository
{
    public function paginate(?string $search, ?string $status): mixed;
    public function create(array $data): void;
    public function update(int $id, array $data): void;
    public function plateExists(string $plate, ?int $exceptId = null): bool;
    public function setArchived(int $id, bool $archived): void;
    public function find(int $id): mixed;
    public function typeBelongsToBrand(int $typeId, int $brandId): bool;
    public function brands(): iterable;
    public function types(): iterable;
}
