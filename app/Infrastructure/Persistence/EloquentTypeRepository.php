<?php
namespace App\Infrastructure\Persistence;
use App\Application\MasterData\TypeRepository;
use App\Models\Type;
final class EloquentTypeRepository implements TypeRepository { public function byBrand(int $brandId): iterable { return Type::where('brand_id',$brandId)->orderBy('name')->get(); } public function create(array $data): void { Type::create($data); } public function update(int $id,array $data): void { Type::findOrFail($id)->update($data); } public function nameExists(int $brandId,string $name,?int $exceptId=null): bool { return Type::where('brand_id',$brandId)->where('name',$name)->when($exceptId,fn($q)=>$q->where('id','!=',$exceptId))->exists(); } }
