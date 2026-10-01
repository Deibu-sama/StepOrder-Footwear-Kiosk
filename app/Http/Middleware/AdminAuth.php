<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->session()->get('steporder_admin');

        if (!$staff) {
            return redirect('/admin/login');
        }

        // Upgrade legacy sessions created before role-based staff accounts existed.
        if (empty($staff['role'])) {
            $staff['role'] = 'admin';
            $staff['name'] = $staff['name'] ?? 'Administrator';
            $request->session()->put('steporder_admin', $staff);
        }

        return $next($request);
    }
}
