<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use App\Services\ContentCache;

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
        // Generate https:// links when the site is configured with an https APP_URL.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Super admins pass every authorization check.
        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);

        // Drag-and-drop reordering in Filament uses a bulk SQL update that skips model events,
        // so refresh the public API cache explicitly.
        Table::configureUsing(fn (Table $table) => $table->afterReordering(fn () => ContentCache::bump()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
