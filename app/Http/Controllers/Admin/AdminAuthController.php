<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly StaffService $staff,
        private readonly ActivityLogService $activity
    ) {}

    public function showLogin()
    {
        if (session('steporder_admin')) {
            return redirect('/admin/dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $envEmail = strtolower(trim((string) env('ADMIN_EMAIL')));
        $envPassword = (string) env('ADMIN_PASSWORD');
        $email = strtolower(trim($data['email']));

        // The environment account remains the permanent super-admin account.
        if (
            $envEmail !== '' &&
            $email === $envEmail &&
            $envPassword !== '' &&
            hash_equals($envPassword, $data['password'])
        ) {
            $request->session()->regenerate();
            $request->session()->put('steporder_admin', [
                'email' => $email,
                'name' => 'Administrator',
                'role' => 'admin',
                'staff_id' => null,
            ]);

            $this->activity->record('LOGIN', ['details' => 'Administrator signed in.']);

            return redirect()->intended('/admin/dashboard');
        }

        $staff = $this->staff->findByEmail($email);

        if (
            $staff &&
            ($staff['role'] ?? '') === 'cashier' &&
            ($staff['active'] ?? false) === true &&
            !empty($staff['password_hash']) &&
            Hash::check($data['password'], $staff['password_hash'])
        ) {
            $request->session()->regenerate();
            $request->session()->put('steporder_admin', [
                'email' => $staff['email'],
                'name' => $staff['name'] ?? 'Cashier',
                'role' => 'cashier',
                'staff_id' => $staff['id'],
            ]);

            $this->activity->record('LOGIN', ['details' => 'Cashier signed in.']);

            return redirect()->intended('/admin/pos');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid credentials or this account is disabled.']);
    }

    public function logout(Request $request)
    {
        if (session('steporder_admin')) {
            $this->activity->record('LOGOUT', ['details' => 'Staff member signed out.']);
        }

        $request->session()->forget('steporder_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
