<?php

namespace Larabookir\Gateway\Payir;

use Larabookir\Gateway\Exceptions\BankException;

class PayirReceiveException extends BankException
{
    public static $errors = [
        -1 => 'ارسال api الزامی می باشد',
        -2 => 'ارسال transId الزامی می باشد',
        -3 => 'درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد',
        -4 => 'فروشنده غیر فعال می باشد',
        -5 => 'تراکنش با خطا مواجه شده است',
    ];

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[ $errorId ] . ' #' . $errorId, $errorId);
    }
}
