<?php
namespace Database\Seeders;
use App\Models\{Brand,Type}; use Illuminate\Database\Seeder;
final class BrandTypeSeeder extends Seeder { public function run(): void { foreach(['Toyota'=>['Avanza','Innova'],'Honda'=>['Brio','CR-V','Civic']] as $brand=>$types) { $b=Brand::firstOrCreate(['name'=>$brand]); foreach($types as $name) Type::firstOrCreate(['brand_id'=>$b->id,'name'=>$name]); } } }
