<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('public-api', function (Request $request) {

            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('authenticated-api', function (Request $request) {

        return Limit::perMinute(120)
            ->by($request->user()->id);
        });

        RateLimiter::for('login', function (Request $request) {

            return Limit::perMinute(5)
                ->by($request->email . $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {

            return Limit::perMinute(3)
                ->by($request->ip());
        });

        RateLimiter::for('cart', function (Request $request) {

            return Limit::perMinute(60)
                ->by($request->user()->id);
        });

        RateLimiter::for('checkout', function (Request $request) {

            return Limit::perMinute(5)
                ->by($request->user()->id);
        });

        RateLimiter::for('wallet', function (Request $request) {

            return Limit::perMinute(3)
                ->by($request->user()->id);
        });

        RateLimiter::for('inventory-update', function (Request $request) {

            return Limit::perMinute(20)
                ->by($request->user()->id);
        });

        RateLimiter::for('admin-actions', function (Request $request) {

            return Limit::perMinute(10)
                ->by($request->user()->id);
        });
    }
}
