<?php

namespace Larabookir\Gateway;

use Illuminate\Support\Facades\Facade;

class GatewayFacade extends Facade
{
	/**
	 * The name of the binding in the IoC container.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'gateway';
	}
}
