<?php

namespace Larabookir\Gateway\Poolam;

use Larabookir\Gateway\Exceptions\BankException;

class PoolamException extends BankException
{
    public static $errors = array(
        100 => "نوع درخواست باید POST باشد",
        101 => "api_key ارسال نشده است یا صحیح نیست",
        102 => "مبلغ ارسال نشده است یا کمتر از 1000 ریال است",
        103 => "آدرس بازگشت ارسال نشده است",
        301 => "خطایی در برقراری با سرور بانک رخ داده است",
        200 => "شناسه پرداخت صحیح نیست",
        201 => "پرداخت انجام نشده است",
        202 => "پرداخت کنسل شده است یا خطایی در مراحل پرداخت رخ داده است",
        1 => "Success",
        4444 => 'کارتی که شما با آن خرید انجام داده اید معتبر نیست',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId] . ' #' . $this->errorId, $this->errorId);
    }
}
