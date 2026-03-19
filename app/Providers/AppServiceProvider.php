<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Observers\EventObserver;
use App\Observers\GroupObserver;
use App\Observers\UserObserver;
use App\Services\GeocodingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeocodingService::class, function () {
            return new GeocodingService(config('services.geocodio.api_key'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Group::observe(GroupObserver::class);
        Event::observe(EventObserver::class);
        User::observe(UserObserver::class);

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });
    }
}
