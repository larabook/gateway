<?php

namespace Hosseinizadeh\Gateway\Payir;

use Hosseinizadeh\Gateway\Exceptions\BankException;

class PayirReceiveException extends BankException
{
    public static $errors = [
        0    =>'درحال   حاضر درگاه بانکی قطع شده و مشکل بزودی برطرف می شود',
        -1    =>'  API Key ارسال نمی شود',
        -2    =>'  Token ارسال نمی شود',
        -3    =>'  API Key ارسال شده اشتباه است',
        -4    =>'  امکان انجام تراکنش برای این پذیرنده وجود ندارد',
        -5    =>'  تراکنش با خطا مواجه شده است',
    ];

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[ $errorId ] . ' #' . $errorId, $errorId);
    }
}
