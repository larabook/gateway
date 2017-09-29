<?php

namespace Larabookir\Gateway;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProviderLaravel4 extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

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




        // for laravel 4.2
        $this->package('larabook/gateway',null,__DIR__.'/../');
		
		
		if (
			File::glob(base_path('/database/migrations/*create_gateway_status_log_table\.php'))
			&& !File::exists(base_path('/database/migrations/2017_04_05_103357_alter_id_in_transactions_table.php'))
		) {
			@File::copy($migrations.'/2017_04_05_103357_alter_id_in_transactions_table.php',base_path('database/migrations/2017_04_05_103357_alter_id_in_transactions_table.php'));
		}
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
