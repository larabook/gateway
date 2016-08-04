<?php

namespace Larabookir\Gateway\JahanPay;

use Larabookir\Gateway\Exceptions\BankException;

class JahanPayException extends BankException
{
    public static $errors = array(
        -32 => 'تراکنش انجام شده اما مبلغ مطابقت ندارد',
        -31 => 'تراکنش انجام نشده است',
        -30 => 'چنین تراکنشی موجود نیست',
        -29 => 'آدرس کال بک خالی است',
        -27 => 'آي پی شما مسدود است',
        -26 => 'درگاه غیر فعال شده است',
        -24 => 'مبلغ نادرست است',
        -23 => 'مبلغ زیاد است',
        -22 => 'مبلغ خیلی کم است -حداقل مبلغ ارسالی به درگاه 100 ت می باشد',
        -21 => 'آي پی براي این درگاه نامعتبر است',
        -20 => 'api نادرست است',
        -6 => 'خطاي اتصال به بانک',
        -9 => 'خطاي سیستمی',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId].' #'.$this->errorId, $this->errorId);
    }
}
