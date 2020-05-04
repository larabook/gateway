<?php

namespace Masihjazayeri\Gateway\Exceptions;

class NotFoundTransactionException extends GatewayException
{
	protected $code=-103;
	protected $message = 'چنین رکورد پرداختی موجود نمی باشد.';
}
