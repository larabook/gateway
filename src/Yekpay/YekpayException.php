<?php

namespace Hosseinizadeh\Gateway\Yekpay;

use Hosseinizadeh\Gateway\Exceptions\BankException;

class YekpayException extends BankException
{
    public static $errors = array(
        -1   => 'The parameters are incomplete',
        -2   => 'Merchant code is incorrect',
        -3   => 'Merchant code is not active',
        -4   => 'Currencies is not valid',
        -5   => 'Maximum/Minimum amount is not valid',
        -6   => 'Your IP is restricted',
        -7   => 'Order id must be unique',
        -8   => 'Currencies is not valid',
        -9   => 'Maximum/Minimum amount is not valid',
        -10  => 'Your IP is restricted',
        -11  => 'Your IP isn’t valid',
        -12  => 'Your IP is unknown',
        -100 => 'Unknown error',
        -30  => 'Unsuccess pay',
        100  => 'Success',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId].' #'.$this->errorId, $this->errorId);
    }
}
