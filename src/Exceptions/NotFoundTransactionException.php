<?php

namespace Larabookir\Gateway\Exceptions;

class NotFoundTransactionException extends \Exception
{
	protected $message = 'چنین رکورد پرداختی موجود نمی باشد.';
}
