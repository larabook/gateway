<?php

namespace Larabookir\Gateway\Paystar;

use Larabookir\Gateway\Exceptions\BankException;

class PaystarException extends BankException {
    public static $errors = array (
        - 1  => "Amount نمیتواند خالی باشد" ,
        - 2  => "کد پین درگاه نمیتواند خالی باشد" ,
        - 3  => "Callback نمیتواند خالی باشد" ,
        - 4  => "Amount باید عددی باشد" ,
        - 5  => "Amount باید بزرگتر از ۱۰۰ باشد" ,
        - 6  => "کد پین درگاه اشتباه هست" ,
        - 7  => "ایپی سرور با ایپی درگاه مطابقت ندارد" ,
        - 8  => " transid نمیتواند خالی باشد" ,
        - 9  => "تراکنش مورد نظر وجود ندارد" ,
        - 10  => "کد پین درگاه با درگاه تراکنش مطابقت ندارد" ,
        - 11  => "مبلغ با مبلغ تراکنش مطابقت ندارد" ,
        - 12  => "بانک انتخابی اشتباه است" ,
        - 13  => "درگاه غیر فعال است" ,
        - 14  => "IP مشتری ارسال نشده است" ,
    );

    public function __construct( $errorId ) {
        $this->errorId = intval( $errorId );

        parent::__construct( @self::$errors[ $this->errorId ] . ' #' . $this->errorId , $this->errorId );
    }
}
