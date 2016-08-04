<?php

namespace Larabookir\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
class GatewayException extends \Exception
{
	protected $code=-200;
	protected $message = 'خطای سرویس.';
}
