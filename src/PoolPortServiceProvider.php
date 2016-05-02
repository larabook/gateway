<?php

namespace Larabookir\PoolPort;

use Illuminate\Support\ServiceProvider;

class PoolPortServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../poolport-sample.php' => config_path('poolport.php')
        ],'config');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('poolport',function()  {
            return new PoolPort(null,config_path('poolport.php'));
        });

    }
}
