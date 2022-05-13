<?php

namespace Larabookir\Gateway\Yekpay;

use Larabookir\Gateway\Exceptions\BankException;

class YekpayException extends BankException
{
    public static $errors = array(
        -1 => "The parameters are incomplete",
        -2 => "Merchant code is incorrect",
        -3 => "Merchant code is not active",
        -4 => "Currencies is not valid",
        -5 => "Maximum/Minimum amount is not valid",
        -6 => "Your IP is restricted",
        -7 => "Order id must be unique",
        -100 => "Unknown error",
        100 => "Success",
        4444 => 'کارتی که شما با آن خرید انجام داده اید معتبر نیست',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId] . ' #' . $this->errorId, $this->errorId);
    }
}
