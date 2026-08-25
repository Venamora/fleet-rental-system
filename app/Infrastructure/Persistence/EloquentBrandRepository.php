<?php
namespace App\Infrastructure\Persistence;
use App\Application\MasterData\BrandRepository;
use App\Models\Brand;
final class EloquentBrandRepository implements BrandRepository { public function all(): iterable { return Brand::with('types')->orderBy('name')->get(); } public function create(array $data): void { Brand::create($data); } public function update(int $id,array $data): void { Brand::findOrFail($id)->update($data); } public function nameExists(string $name,?int $exceptId=null): bool { return Brand::where('name',$name)->when($exceptId,fn($q)=>$q->where('id','!=',$exceptId))->exists(); } }
