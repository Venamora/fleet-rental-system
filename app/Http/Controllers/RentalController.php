<?php

namespace App\Http\Controllers;

use App\Application\Rentals\{RentalQueryService, RentalService};
use Illuminate\Http\Request;
use InvalidArgumentException;

final class RentalController extends Controller
{
    public function index(Request $request, RentalQueryService $queries) { return view('rentals.index', ['rentals'=>$queries->list($request->only(['status','vehicle_id','customer_id','start_date','end_date'])),'vehicles'=>$queries->vehicles(),'customers'=>$queries->customers()]); }
    public function show(int $rental, RentalQueryService $queries) { return view('rentals.show', ['rental'=>$queries->detail($rental),'history'=>$queries->history($rental),'vehicles'=>$queries->vehicles(),'customers'=>$queries->customers()]); }
    public function history(int $rental, RentalQueryService $queries) { return view('rentals.history', ['rental'=>$rental,'history'=>$queries->history($rental)]); }
    public function catalogs(RentalQueryService $queries) { return response()->json(['vehicles'=>$queries->vehicles(),'customers'=>$queries->customers()]); }
    public function availability(Request $request, RentalQueryService $queries) { $data=$request->validate(['start_date'=>'required|date_format:Y-m-d','end_date'=>'required|date_format:Y-m-d']); try { return response()->json(['vehicles'=>$queries->availability($data['start_date'],$data['end_date'])]); } catch (InvalidArgumentException $e) { return back()->withErrors(['dates'=>'Tanggal sewa tidak valid.'])->withInput(); } }
    public function pricePreview(Request $request, RentalQueryService $queries) { $data=$request->validate(['vehicle_id'=>'required|exists:vehicles,id','start_date'=>'required|date_format:Y-m-d','end_date'=>'required|date_format:Y-m-d']); try { return response()->json($queries->pricePreview((int)$data['vehicle_id'], $data['start_date'], $data['end_date'])); } catch (InvalidArgumentException $e) { return response()->json(['message'=>'Tanggal sewa tidak valid.'], 422); } }
    public function store(Request $request, RentalService $service) { return $this->write($request, fn (array $d) => $service->create((int)$d['vehicle_id'], (int)$d['customer_id'], $d['start_date'], $d['end_date'])); }
    public function update(Request $request, int $rental, RentalService $service) { return $this->write($request, fn (array $d) => $service->edit($rental, (int)$d['vehicle_id'], (int)$d['customer_id'], $d['start_date'], $d['end_date'])); }
    public function cancel(Request $request, int $rental, RentalService $service) { $data=$request->validate(['reason'=>'required|string']); try { $service->cancel($rental,$data['reason']); } catch (InvalidArgumentException $e) { return back()->withErrors(['reason'=>$e->getMessage()])->withInput(); } return back()->with('status','Pemesanan dibatalkan.'); }
    public function complete(int $rental, RentalService $service) { try { $service->complete($rental); } catch (InvalidArgumentException $e) { return back()->withErrors(['status'=>$e->getMessage()]); } return back()->with('status','Pemesanan selesai.'); }
    private function write(Request $request, \Closure $operation) { $data=$request->validate(['vehicle_id'=>'required|exists:vehicles,id','customer_id'=>'required|exists:customers,id','start_date'=>'required|date_format:Y-m-d','end_date'=>'required|date_format:Y-m-d']); try { $operation($data); } catch (InvalidArgumentException $e) { return back()->withErrors(['dates'=>$e->getMessage()])->withInput(); } return redirect('/rentals')->with('status','Pemesanan tersimpan.'); }
}
