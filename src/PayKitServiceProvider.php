<?php

namespace Solivellaluisaberto\PayKit;

use Illuminate\Support\ServiceProvider;

class PayKitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pay-kit.php',
            'pay-kit'
        );

        $this->app->singleton('pay-kit', function ($app) {
            return new PayKit();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/pay-kit.php' => config_path('pay-kit.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

