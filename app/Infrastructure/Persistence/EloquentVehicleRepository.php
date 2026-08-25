<?php
namespace App\Infrastructure\Persistence;
use App\Application\Vehicles\VehicleRepository;
use App\Models\{Vehicle,Rental};
use App\Domain\Shared\Contracts\Clock;
final class EloquentVehicleRepository implements VehicleRepository
{
    public function __construct(private Clock $clock) {}
    public function paginate(?string $search, ?string $status): mixed { $page=Vehicle::with(['brand','type'])->when($search, fn($q,$v)=>$q->where(function($q) use ($v) {$q->where('plate','like','%'.strtoupper(trim($v)).'%')->orWhereHas('brand',fn($b)=>$b->where('name','like','%'.$v.'%'))->orWhereHas('type',fn($t)=>$t->where('name','like','%'.$v.'%'));}))->when($status==='archived',fn($q)=>$q->whereNotNull('archived_at'))->when($status==='active',fn($q)=>$q->whereNull('archived_at'))->paginate(15)->withQueryString(); $today=$this->clock->now()->format('Y-m-d'); $page->getCollection()->each(function(Vehicle $vehicle) use ($today): void { $vehicle->setAttribute('derived_status', $vehicle->archived_at ? 'archived' : (Rental::where('vehicle_id',$vehicle->id)->where('status','active')->whereDate('start_date','<=',$today)->whereDate('effective_end_date','>=',$today)->exists() ? 'di-sewa' : 'tersedia')); }); return $page; }
    public function create(array $data): void { Vehicle::create($data); }
    public function update(int $id, array $data): void { Vehicle::findOrFail($id)->update($data); }
    public function plateExists(string $plate, ?int $exceptId = null): bool { return Vehicle::where('plate',$plate)->when($exceptId,fn($q)=>$q->where('id','!=',$exceptId))->exists(); }
    public function setArchived(int $id, bool $archived): void { Vehicle::findOrFail($id)->update(['archived_at'=>$archived ? $this->clock->now() : null]); }
    public function find(int $id): mixed { return Vehicle::findOrFail($id); }
    public function typeBelongsToBrand(int $typeId, int $brandId): bool { return \App\Models\Type::whereKey($typeId)->where('brand_id',$brandId)->exists(); }
    public function brands(): iterable { return \App\Models\Brand::orderBy('name')->get(); }
    public function types(): iterable { return \App\Models\Type::orderBy('name')->get(); }
}
