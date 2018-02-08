<?php

namespace Larabookir\Gateway\Paypal;

use Larabookir\Gateway\Exceptions\BankException;

class PaypalException extends BankException
{
    public static $errors = array(
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId].' #'.$this->errorId, $this->errorId);
    }
}
