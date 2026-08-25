<?php
namespace App\Http\Controllers;
use App\Application\Vehicles\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request, VehicleService $service) { $catalogs=$service->catalogs(); return view('vehicles.index', ['vehicles'=>$service->list($request->search, $request->status), 'brandOptions'=>$catalogs['brands'], 'typeOptions'=>$catalogs['types']]); }
    public function store(Request $request, VehicleService $service) { $data=$request->validate(['brand_id'=>'required|exists:brands,id','type_id'=>'required|exists:types,id','plate'=>'required|string|max:30','daily_rate_cents'=>'required|integer|min:1','year'=>'nullable|integer|min:1886|max:'.(date('Y')+1),'color'=>'nullable|string|max:50']); try { $service->create($data); } catch (\DomainException $e) { return back()->withErrors(['plate'=>$e->getMessage(),'type_id'=>$e->getMessage()])->withInput(); } return redirect('/vehicles')->with('status','Kendaraan tersimpan.'); }
    public function update(Request $request, int $vehicle, VehicleService $service) { $data=$request->validate(['brand_id'=>'required|exists:brands,id','type_id'=>'required|exists:types,id','plate'=>'required|string|max:30','daily_rate_cents'=>'required|integer|min:1','year'=>'nullable|integer|min:1886|max:'.(date('Y')+1),'color'=>'nullable|string|max:50']); try { $service->update($vehicle, $data); } catch (\DomainException $e) { return back()->withErrors(['type_id'=>$e->getMessage()])->withInput(); } return redirect('/vehicles'); }
    public function archive(Request $request, int $vehicle, VehicleService $service) { $request->validate(['confirmed'=>'accepted']); $service->archive($vehicle); return back(); }
    public function restore(Request $request, int $vehicle, VehicleService $service) { $request->validate(['confirmed'=>'accepted']); $service->restore($vehicle); return back(); }
}
