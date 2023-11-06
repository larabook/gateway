<?php

namespace Larabookir\Gateway\Samankish;

use Larabookir\Gateway\Exceptions\BankException;

class SamankishReceiveException extends BankException
{
    public static $errors = array(
        -2   => 'تراکنش یافت نشد.',
        -6   => 'بیش از نیم ساعت از زمان اجرای تراکنش گذشته است.',
        0    => 'موفق',
        2    => 'درخواست تکراری می باشد.',
        -105 => 'ترمینال ارسالی در س یستم موجود نمی باشد',
        -104 => 'ترمینال ارسالی غیرفعال می باشد',
        -1   => 'آدرس آ ی پی درخواستی غیر مجاز می باشد',
    );

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[$errorId] . ' #' . $errorId, $errorId);
    }
}
