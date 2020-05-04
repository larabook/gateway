<?php

namespace Masihjazayeri\Gateway\Saderat;

use Masihjazayeri\Gateway\Exceptions\BankException;

class SaderatException extends BankException
{

    public static $errors = array(
        "OK" => "پرداخت با موفقیت انجام شد",
        'Canceled By User' => 'تراکنش توسط خریدار کنسل شد',
        'Invalid Amount' => 'مبلغ سند برگشتی از مبلغ تراکنش اصلی بیشتر است',
        'Merchant Invalid' => 'پذیرنده فروشگاهی نامعتبر است',
        'Do Not Honour' => 'از انجام تراکنش صرف نظر شد',
        'Honour With Identification' => 'با تشخیص هویت دارنده کارت،تراکنش موفق می باشد',
        'Invalid Transaction' => 'درخواست برگشت تراکنش رسیده است در حالی که تراکنش اصلی پیدا نمی شود',
        'Invalid Card Number' => 'شماره کارت اشتباه است',
        'No Such Issuer' => 'چنین صادر کننده کارتی وجود ندارد',
        'Expired Card Pick Up' => 'از تاریخ انقضای کارت گذشته است و کارت دیگر معتبر نیست',
        'Incorrect PIN' => 'رمز کارت (PIN) اشتباه وارد شده است',
        'No Sufficient Funds' => 'موجودی به اندازه کافی در حساب شما نیست',
        'Issuer Down Slm' => 'سیستم کارت بانک صادر کننده فعال نیست',
        'TME Error' => 'خطا در شبکه بانکی',
        'Exceeds Withdrawal Amount Limit' => 'مبلغ بیش از سقف برداشت است',
        'Transaction Cannot Be Completed' => 'امکان سند خوردن وجود ندارد',
        'Allowable PIN Tries Exceeded Pick Up' => 'رمز کارت (PIN) 3 مرتبه اشتباه وارد شده است در نتیجه کارت شما غیر فعال خواهد شد',
        'Response Received Too Late' => 'تراکنش در شبکه بانکی Timeout خورده است',
        'Suspected Fraud Pick Up' => 'فیلد CV2V و یا فیلد ExpDate اشتباه وارد شده و یا اصلا وارد نشده است',



        -1 => "تراکنش پیدا نشد.",
        -2 => "تراکنش قبال Reverse شده است.",
        -3 => "r Total خطای عمومی – خطای Exception ها",
        -4 => "امکان انجام درخواست برای این تراکنش وجود ندارد.",
        -5 => "IP Address فروشنده نا معتبر است",
        -6 => "عدم فعال بودن سرویس برگشت تراکنش برای پذیرنده"
    );

    public function __construct($errorRef)
    {
        $this->errorRef = $errorRef;

        parent::__construct(@self::$errors[$this->errorRef].' ('.$this->errorRef.')', intval($this->errorRef));
    }
}
