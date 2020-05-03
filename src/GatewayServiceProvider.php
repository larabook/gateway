<?php

namespace Imerit\Gateway;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class GatewayServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Actual provider
     *
     * @var \Illuminate\Support\ServiceProvider
     */
    protected $provider;

    /**
     * Create a new service provider instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->provider = $this->getProvider();
    }

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
        if (method_exists($this->provider, 'boot')) {
            return $this->provider->boot();
        }
	}

    /**
     * Return ServiceProvider according to Laravel version
     *
     * @return \Intervention\Image\Provider\ProviderInterface
     */
    private function getProvider()
    {
        if (version_compare(\Illuminate\Foundation\Application::VERSION, '6.0', '<')) {
            $provider = 'Imerit\Gateway\GatewayServiceProviderLaravel6';
        }else {
            $provider = 'Imerit\Gateway\GatewayServiceProviderLaravel7';
        }

        return new $provider($this->app);
    }

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
	    return $this->provider->register();
	}
}
