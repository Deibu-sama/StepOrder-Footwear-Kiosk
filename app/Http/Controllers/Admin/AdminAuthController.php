<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class AdminAuthController extends Controller
{
 public function showLogin(){if(session('steporder_admin'))return redirect('/admin/dashboard');return view('admin.auth.login');}
 public function login(Request $request){$data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);if(hash_equals((string)env('ADMIN_EMAIL'),$data['email'])&&hash_equals((string)env('ADMIN_PASSWORD'),$data['password'])){$request->session()->regenerate();$request->session()->put('steporder_admin',['email'=>$data['email']]);return redirect()->intended('/admin/dashboard');}return back()->withInput($request->only('email'))->withErrors(['email'=>'Invalid admin or cashier credentials.']);}
 public function logout(Request $request){$request->session()->forget('steporder_admin');$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/admin/login');}
}