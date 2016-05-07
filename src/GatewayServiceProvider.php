<?php

namespace Larabookir\Gateway;

use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$config = __DIR__ . '/../config/gateway.php';
		$migrations = __DIR__ . '/../migrations/';
		$views = __DIR__ . '/../views/';

		//php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider --tag=config
		$this->publishes([
			$config => config_path('gateway.php'),
		], 'config');

		// php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider --tag=migrations
		$this->publishes([
			$migrations => database_path('/migrations')
		], 'migrations');


		$this->loadViewsFrom($views, 'gateway');

		// php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider --tag=views
		$this->publishes([
			$views => base_path('resources/views/vendor/gateway'),
		], 'views');

		//$this->mergeConfigFrom( $config,'gateway')
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('gateway', function () {
			return new GatewayResolver();
		});

	}
}
