<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KioskAccess
{
    public function __construct(private readonly SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $settings = $this->settings->all();

        if (!empty($settings['maintenance_mode'])) {
            return response()->view('kiosk.maintenance', compact('settings'), 503);
        }

        return $next($request);
    }
}
