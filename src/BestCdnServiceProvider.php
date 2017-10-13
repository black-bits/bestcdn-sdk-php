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

    public function register()
    {
        $this->app->singleton('BestCdn', function ($app) {
            return new BestCdnClient(config('bestcdn-sdk'));
        });

    }

    public function provides()
    {
        return ['BestCdn'];
    }
}