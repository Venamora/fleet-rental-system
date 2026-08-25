<?php
namespace App\Http\Controllers;
use App\Application\MasterData\{BrandService,TypeService};
use App\Models\Brand;
use Illuminate\Http\Request;
final class MasterDataController extends Controller
{
 public function index(BrandService $brands) { return response()->json(['brands'=>$brands->list()]); }
 public function brands(Request $r, BrandService $s) { $d=$r->validate(['name'=>'required|string|max:100']); try{$s->create($d);}catch(\DomainException $e){return back()->withErrors(['name'=>$e->getMessage()])->withInput();} return back(); }
 public function updateBrand(Request $r,int $brand,BrandService $s) { $d=$r->validate(['name'=>'required|string|max:100']); try{$s->update($brand,$d);}catch(\DomainException $e){return back()->withErrors(['name'=>$e->getMessage()])->withInput();} return back(); }
 public function types(Request $r, TypeService $s) { return response()->json(['types'=>$s->byBrand((int)$r->integer('brand_id'))]); }
 public function storeType(Request $r,TypeService $s) { $d=$r->validate(['brand_id'=>'required|exists:brands,id','name'=>'required|string|max:100']); try{$s->create($d);}catch(\DomainException $e){return back()->withErrors(['name'=>$e->getMessage()])->withInput();} return back(); }
 public function updateType(Request $r,int $type,TypeService $s) { $d=$r->validate(['brand_id'=>'required|exists:brands,id','name'=>'required|string|max:100']); try{$s->update($type,$d);}catch(\DomainException $e){return back()->withErrors(['name'=>$e->getMessage()])->withInput();} return back(); }
}
