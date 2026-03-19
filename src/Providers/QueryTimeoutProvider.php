<?php

namespace Juanparati\QueryTimeout\Providers;

use Illuminate\Support\ServiceProvider;
use Juanparati\QueryTimeout\QueryTimeout;

/**
 * Laravel service provider.
 */
class QueryTimeoutProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/query-timeout.php' => config_path('query-timeout.php'),
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/query-timeout.php', 'query-timeout');

        $this->app->singleton(QueryTimeout::class, function ($app) {
            return new QueryTimeout($app['config']->get('query-timeout'));
        });
    }
}
