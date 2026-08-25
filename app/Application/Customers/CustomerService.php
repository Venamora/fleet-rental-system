<?php
namespace App\Application\Customers;

use App\Domain\Customers\{Email, IndonesianMobile};

final readonly class CustomerService
{
    public function __construct(private CustomerRepository $customers) {}
    public function list(): mixed { return $this->customers->paginate(); }
    public function create(array $data): void { $data = $this->normalize($data); if ($this->customers->identityExists($data['email'], $data['phone'])) throw new \DomainException('Data pelanggan sudah digunakan.'); $this->customers->create($data); }
    public function update(int $id, array $data): void { $data = $this->normalize($data); if ($this->customers->identityExists($data['email'], $data['phone'], $id)) throw new \DomainException('Data pelanggan sudah digunakan.'); $this->customers->update($id, $data); }
    private function normalize(array $data): array { $data['email'] = Email::from($data['email'])->value(); $data['phone'] = IndonesianMobile::from($data['phone'])->value(); return $data; }
}
