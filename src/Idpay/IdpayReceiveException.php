<?php

namespace Larabookir\Gateway\Idpay;

use Larabookir\Gateway\Exceptions\BankException;

class IdpayReceiveException extends BankException
{
    public static $errors = [
        -1 => 'ارسال api الزامی می باشد',
        -2 => 'ارسال transId الزامی می باشد',
        -3 => 'درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد',
        -4 => 'فروشنده غیر فعال می باشد',
        -5 => 'تراکنش با خطا مواجه شده است',

        11 => 'کاربر مسدود شده است.',
        12 => 'API Key یافت نشد.',
        13 => 'درخواست شما از  ارسال شده است. این IP با IP های ثبت شده در وب سرویس همخوانی ندارد.',
        14 => 'وب سرویس شما در حال بررسی است و یا تایید نشده است.',
        21 => 'حساب بانکی متصل به وب سرویس تایید نشده است.',
        22 => 'وب سریس یافت نشد.',
        23 => 'اعتبار سنجی وب سرویس ناموفق بود.',
        24 => 'حساب بانکی مرتبط با این وب سرویس غیر فعال شده است.',
        
        53 => 'تایید پرداخت امکان پذیر نیست.',
        54 => 'مدت زمان تایید پرداخت سپری شده است.',
        51 => 'تراکنش ایجاد نشد.',
    ];

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[ $errorId ] . ' #' . $errorId, $errorId);
    }
}
