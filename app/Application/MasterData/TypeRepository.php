<?php
namespace App\Application\MasterData;
interface TypeRepository { public function byBrand(int $brandId): iterable; public function create(array $data): void; public function update(int $id, array $data): void; public function nameExists(int $brandId, string $name, ?int $exceptId = null): bool; }
