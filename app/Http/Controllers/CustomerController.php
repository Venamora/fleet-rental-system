<?php
namespace App\Http\Controllers;
use App\Application\Customers\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(CustomerService $service) { return view('customers.index', ['customers'=>$service->list()]); }
    public function store(Request $request, CustomerService $service) { $data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email','phone'=>'required|string']); try { $service->create($data); } catch (\InvalidArgumentException $e) { return back()->withErrors(['phone'=>'Nomor ponsel Indonesia tidak valid.'])->withInput(); } catch (\DomainException $e) { return back()->withErrors(['email'=>$e->getMessage()])->withInput(); } return redirect('/customers'); }
    public function update(Request $request, int $customer, CustomerService $service) { $data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email','phone'=>'required|string']); try { $service->update($customer, $data); } catch (\InvalidArgumentException $e) { return back()->withErrors(['phone'=>'Nomor ponsel Indonesia tidak valid.'])->withInput(); } catch (\DomainException $e) { return back()->withErrors(['email'=>$e->getMessage()])->withInput(); } return redirect('/customers'); }
}
