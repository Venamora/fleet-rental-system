<?php
namespace App\Application\MasterData;
interface BrandRepository { public function all(): iterable; public function create(array $data): void; public function update(int $id, array $data): void; public function nameExists(string $name, ?int $exceptId = null): bool; }
