<?php

namespace AbramCatalyst\Flutterwave;

use Illuminate\Support\ServiceProvider;

class FlutterwaveServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/flutterwave.php',
            'flutterwave'
        );

        $this->app->singleton('flutterwave', function ($app) {
            return new FlutterwaveService($app['config']['flutterwave']);
        });

        $this->app->alias('flutterwave', FlutterwaveService::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/flutterwave.php' => config_path('flutterwave.php'),
        ], 'flutterwave-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'flutterwave-migrations');

        // Publish models
        $this->publishes([
            __DIR__ . '/Models/FlutterwaveTransaction.php' => app_path('Models/FlutterwaveTransaction.php'),
        ], 'flutterwave-models');

        // Load routes only if enabled in config (default: true)
        if (config('flutterwave.enable_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}

