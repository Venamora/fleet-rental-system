<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create() { return view('auth.login'); }
    public function store(Request $request) {
        $credentials = $request->validate(['username'=>'required|string','password'=>'required|string']);
        if ($credentials['username'] !== config('auth.seeded_admin_username') || ! Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']])) return back()->withErrors(['login'=>'Kredensial tidak valid.'])->onlyInput('username');
        $request->session()->regenerate(); return redirect()->intended('/vehicles');
    }
    public function destroy(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login'); }
}
