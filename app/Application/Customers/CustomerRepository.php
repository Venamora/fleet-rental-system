<?php
namespace App\Application\Customers;

interface CustomerRepository
{
    public function paginate(): mixed;
    public function create(array $data): void;
    public function update(int $id, array $data): void;
    public function identityExists(string $email, string $phone, ?int $exceptId = null): bool;
}
