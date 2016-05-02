<?php

namespace Larabookir\PoolPort;

use Illuminate\Support\Facades\Facade;

class PoolPortFacade extends Facade
{
	/**
	 * The name of the binding in the IoC container.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'poolport';
	}
}
