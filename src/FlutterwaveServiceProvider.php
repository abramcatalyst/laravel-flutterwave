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

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \AbramCatalyst\Flutterwave\Console\Commands\FlutterwaveHealthCheck::class,
            ]);
        }

        // Validate configuration during boot (only in non-console or when explicitly enabled)
        $this->validateConfiguration();

        // Load routes only if enabled in config (default: true)
        if (config('flutterwave.enable_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    /**
     * Validate package configuration.
     *
     * @return void
     */
    protected function validateConfiguration(): void
    {
        // Only validate in non-console mode or when explicitly enabled
        // Skip validation during console commands to avoid breaking artisan commands
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        $config = $this->app['config']->get('flutterwave', []);

        // Check for required configuration
        if (empty($config['public_key']) || empty($config['secret_key'])) {
            // Don't throw exception during boot, just log a warning
            // The service will throw proper exceptions when used
            if (function_exists('logger')) {
                logger()->warning('Flutterwave: Public key or secret key not configured. Run "php artisan flutterwave:health-check" to verify installation.');
            }
        }
    }
}

