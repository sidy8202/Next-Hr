<?php

namespace App\Providers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Directive Blade personnalisée
        Blade::if('canDo', function (string $action, string $resource) {
            return auth()->check() && auth()->user()->canDo($action, $resource);
        });

        // Rate Limiter personnalisé : e-mail + IP
       RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by(Str::lower((string) $request->input('email')) . '|' . $request->ip());
        });
    }
}
