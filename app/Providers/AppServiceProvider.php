<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Super admins pass every authorization check.
        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
