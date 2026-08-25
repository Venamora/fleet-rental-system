<?php
namespace App\Application\Vehicles;

use App\Domain\Vehicles\Plate;
use App\Application\Shared\Transaction;
use App\Domain\Shared\Contracts\Clock;

final readonly class VehicleService
{
    public function __construct(private VehicleRepository $vehicles, private Transaction $transaction, private Clock $clock) {}
    public function list(?string $search, ?string $status): mixed { return $this->vehicles->paginate($search, $status); }
    public function catalogs(): array { return ['brands'=>$this->vehicles->brands(),'types'=>$this->vehicles->types()]; }
    public function create(array $data): void { $data['plate'] = Plate::from($data['plate'])->value(); $this->assertTypeBrand($data); if ($this->vehicles->plateExists($data['plate'])) throw new \DomainException('Plat nomor sudah digunakan.'); $this->vehicles->create($data); }
    public function update(int $id, array $data): void { $data['plate'] = Plate::from($data['plate'])->value(); $this->assertTypeBrand($data); if ($this->vehicles->plateExists($data['plate'], $id)) throw new \DomainException('Plat nomor sudah digunakan.'); $this->vehicles->update($id, $data); }
    private function assertTypeBrand(array $data): void { if (!$this->vehicles->typeBelongsToBrand((int)$data['type_id'], (int)$data['brand_id'])) throw new \DomainException('Tipe tidak sesuai dengan merk.'); }
    public function archive(int $id): void { $this->transaction->run(fn () => $this->vehicles->setArchived($id, true)); }
    public function restore(int $id): void { $this->transaction->run(fn () => $this->vehicles->setArchived($id, false)); }
}
