<?php

namespace Larabookir\Gateway;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Larabookir\Gateway\GatewayResolver
 * @method  static GatewayResolver make($port)
 * @method  static GatewayResolver verify()
 * @method  static GatewayResolver mellat()
 * @method  static GatewayResolver sadad()
 * @method  static GatewayResolver zarinpal()
 * @method  static GatewayResolver payline()
 * @method  static GatewayResolver jahanpay()
 * @method  static GatewayResolver parsian()
 * @method  static GatewayResolver pasargad()
 * @method  static GatewayResolver saman()
 * @method  static GatewayResolver asanpardakht()
 * @method  static GatewayResolver paypal()
 * @method  static GatewayResolver payir()
 * @method  static GatewayResolver irankish()
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
