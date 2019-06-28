<?php

namespace Larautility\Gateway\Payir;

use Larautility\Gateway\Exceptions\BankException;

class PayirSendException extends BankException
{
    public static $errors = [
        -0       => 'در حال حاضر درگاه پرداخت قطع می باشد. به زودی مشکل برطرف خواهد شد' ,
        -1       => 'ارسال api الزامی می باشد',
        -2       => 'ارسال amount ( مبلغ تراکنش ) الزامی می باشد',
        -3       => 'amount ( مبلغ تراکنش )باید به صورت عددی باشد',
        -4       => 'amount نباید کمتر از 1000 باشد',
        -5       => 'ارسال redirect الزامی می باشد',
        -6       => 'درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد',
        -7       => 'فروشنده غیر فعال می باشد',
        -8       => 'آدرس بازگشتی با آدرس درگاه پرداخت ثبت شده همخوانی ندارد',
        'failed' => 'تراکنش با خطا مواجه شد',
    ];

    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[ $errorId ] . ' #' . $errorId, $errorId);
    }
}
