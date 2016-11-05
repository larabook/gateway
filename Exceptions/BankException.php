<?php

namespace Larabookir\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
class BankException extends \Exception
{
	protected $code=-100;
	protected $message = 'خطای بانک.';
}
