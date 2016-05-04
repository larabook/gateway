<?php

namespace Larabookir\Gateway;

use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider --tag=config
        $this->publishes([
            __DIR__ . '/config/gateway.php' => config_path('gateway.php'),
        ],'config');

        // php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider --tag=migrations
        $this->publishes([
            __DIR__.'/migrations/' => database_path('/migrations')
        ],'migrations');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('gateway',function()  {
            return new Gateway();
        });

    }
}
