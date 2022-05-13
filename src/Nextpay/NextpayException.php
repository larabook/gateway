<?php

namespace Larabookir\Gateway\Nextpay;

use Larabookir\Gateway\Exceptions\BankException;

class NextpayException extends BankException
{
    public static $errors = array(
        0 => "پرداخت تکمیل و با موفقیت انجام شده است",
        -1 => "منتظر ارسال تراکنش و ادامه پرداخت",
        -2 => "پرداخت رد شده توسط کاربر یا بانک",
        -3 => "پرداخت در حال انتظار جواب بانک",
        -4 => "پرداخت لغو شده است",
        -20 => "کد api_key ارسال نشده است",
        -21 => "empty trans_id param send",
        -22 => "amount in not send",
        -23 => "callback in not send",
        -24 => "amount incorrect",
        -25 => "trans_id resend and not allow to payment",
        -26 => "Token not send",
        -27 => "order_id incorrect",
        -30 => "amount less of limite payment",
        -31 => "fund not found",
        -32 => "callback error",
        -33 => "api_key incorrect",
        -34 => "trans_id incorrect",
        -35 => "type of api_key incorrect",
        -36 => "order_id not send",
        -37 => "transaction not found",
        -38 => "token not found",
        -39 => "کلید مجوز دهی موجود نیست",
        -40 => "api_key is blocked",
        -41 => "params from bank invalid",
        -42 => "payment system problem",
        -43 => "gateway not found",
        -44 => "response bank invalid",
        -45 => "payment system deactived",
        -46 => "request incorrect",
        -47 => "gateway is deleted or not found",
        -48 => "commission rate not detect",
        -49 => "trans repeated",
        -50 => "account not found",
        -51 => "user not found",
        -60 => "email incorrect",
        -61 => "national code incorrect",
        -62 => "postal code incorrect",
        -63 => "postal add incorrect",
        -64 => "desc incorrect",
        -65 => "name family incorrect",
        -66 => "tel incorrect",
        -67 => "account name incorrect",
        -68 => "product name incorrect",
        -69 => "callback success incorrect",
        -70 => "callback failed incorrect",
        -71 => "phone incorrect",
        -72 => "bank not response",
        -73 => "callback_uri incorrect",
        -90 => "تراکنش لغو شد! مبلغ به کارت بانکی شما واریز خواهد شد",
        4444 => 'کارتی که شما با آن خرید انجام داده اید معتبر نیست',
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId] . ' #' . $this->errorId, $this->errorId);
    }
}
