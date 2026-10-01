<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->session()->get('steporder_admin');

        if (!$staff || ($staff['role'] ?? 'cashier') !== 'admin') {
            return redirect('/admin/dashboard')
                ->with('error', 'Administrator access is required for that area.');
        }

        return $next($request);
    }
}
