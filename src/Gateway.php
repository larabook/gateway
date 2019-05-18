<?php

namespace Larautility\Gateway;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Larautility\Gateway\GatewayResolver
 */
class Gateway extends Facade
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
