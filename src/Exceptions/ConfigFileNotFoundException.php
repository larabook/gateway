<?php

namespace Larautility\Gateway\Exceptions;

class ConfigFileNotFoundException extends GatewayException {
	protected $code=-105;
	protected $message='فایل تنظیمات یافت نشد.';
}
