<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }
}