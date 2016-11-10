<?php

namespace Larabookir\Gateway\Payline;

use Larabookir\Gateway\Exceptions\BankException;

class PaylineSendException extends BankException
{
    public static $errors = array(
        -1 => 'api ارسالی با نوع api تعریف شده در payline سازگار نیست.',
        -2 => 'مقدار amount داده عددی نمی باشد و یا کمتر از 1000 ریال است.',
        -3 => 'مقدار redirect رشته null است.',
        -4 => 'درگاهی با اطلاعات ارسالی شما یافت نشد و یا در حالت انتظار می باشد.'
    );

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[$errorId].' #'.$errorId, $errorId);
    }
}
