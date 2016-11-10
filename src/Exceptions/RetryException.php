<?php

namespace Larabookir\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
class RetryException extends GatewayException
{
	protected $code=-101;
	protected $message = 'نتیجه تراکنش قبلا از طرف بانک اعلام گردیده.';
}
