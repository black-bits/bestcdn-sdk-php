<?php

namespace BlackBits\BestCdn;

use Illuminate\Support\ServiceProvider;


class BestCdnServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/bestcdn-sdk.php' => config_path('bestcdn-sdk.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(BestCdn::class, function ($app) {
            return new BestCdn($app['config']['bestcdn-sdk']);
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [BestCdn::class];
    }
}