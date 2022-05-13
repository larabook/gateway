<?php

namespace Larabookir\Gateway\Mrpay;

use Larabookir\Gateway\Exceptions\BankException;

class MrpaySendException extends BankException {
    public static $errors = [
        0        => 'تراکنش با خطا مواجه شد' ,
        -1      => 'amount میتواند خالی باشد' ,
        -2      => 'کد پین درگاه نمیتواند خالی باشد' ,
        -3      => 'callback نمیتواند خالی باشد' ,
        -4      => 'amount باید عددی باشد' ,
        -5      => 'amount باید بزرگتر از ۱۰۰ باشد	' ,
        -6      => 'کد پین درگاه اشتباه هست' ,
        -7      => 'ایپی سرور با ایپی درگاه مطابقت ندارد' ,
        -8      => 'transid نمیتواند خالی باشد	' ,
        -9      => 'تراکنش مورد نظر وجود ندارد	' ,
        -10     => 'کد پین درگاه با درگاه تراکنش مطابقت ندارد	' ,
        -11     => 'مبلغ با مبلغ تراکنش مطابقت ندارد	' ,
        -12     => 'بانک وارد شده اشتباه میباشد' ,
        -13     => 'درگاه غیر فعال است	' ,
        -14     => 'درگاه برروی سایت دیگری درحال استفاده است' ,
        'failed' => 'تراکنش با خطا مواجه شد' ,
    ];

    public function __construct( $errorId ) {
        $this->errorId = $errorId;

        parent::__construct( @self::$errors[ $errorId ] . ' #' . $errorId , $errorId );
    }
}
