<?php

namespace Larabookir\Gateway\Samankish;

use Larabookir\Gateway\Exceptions\BankException;

class SamankishCallbackException extends BankException
{
    public static $errors = array(
        1  => 'کاربر انصراف داده است',
        2  => 'پرداخت با موفقیت انجام شد',
        3  => 'پرداخت انجام نشد.',
        4  => 'کاربر در بازه زمانی تعیین شده پاسخی ارسال نکرده است.',
        5  => 'پارامترهای ارسالی نامعتبر است.',
        8  => 'آدرس سرور پذیرنده نامعتبر است (در پرداخت های بر پایهتوکن)',
        10 => 'توکن ارسال شده یافت نشد.',
        11 => 'با این شماره ترمینال فقط تراکنش های توکنی قابل پرداخت هستند.',
        12 => 'شماره ترمینال ارسال شده یافت نشد.',
        21 => 'محدودیت های مدل چند حسابی رعایت نشده ',
    );

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[$errorId] . ' #' . $errorId, $errorId);
    }
}
