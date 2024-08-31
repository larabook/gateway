<?php

namespace Hosseinizadeh\Gateway\Novinnew;

use Hosseinizadeh\Gateway\Exceptions\BankException;

class NovinnewException extends BankException
{
    public static $errors = array(
        -138 => 'عملیات پرداخت توسط کاربر لغو شد',
        -1531 => " عملیات پرداخت توسط کاربر لغو شد",
        0 => "تراکنش با موفقیت انجام شد",
        1 => "صادرکننده کارت از انجام تراکنش صرف نظر کرد.",
        2 => "عملیات تاییدیه این تراکنش قب ً با موفقیت صورت پذیرفته است.",
        3 => "پذیرنده فروشگاهی نامعتبر می باشد",
        4 => "کارت توسط دستگاه ضبط شود.",
        5 => "به تراکنش رسیدگی نشد.",
        6 => "بروز خطا.",
        7 => "به دلیل شرایط خاص کارت توسط دستگاه ضبط شود",
        8 => "با تشخیص هویت دارنده ی کارت، تراکنش موفق می باشد.",
        12 => "تراکنش نامعتبر است.",
        13 => "مبلغ تراکنش اص حیه نادرست است.",
        14 => "شماره کارت ارسالی نامعتبر است.(وجود ندارد)",
        15 => "صادر کننده ی کارت نامعتبر است.(وجود ندارد)",
        16 => "تراکنش مورد تایید است و اط عات شیار سوم کارت به روز رسانی شود",
        19 => "تراکنش مجدداً ارسال شود.",
        23 => "کارمزد ارسالی پذیرنده غیر قابل قبول است.",
        25 => "تراکنش اصلی یافت نشد.",
        30 => "قالب پیام دارای اشکال است.",
        31 => "پذیرنده توسط سوئیچ پشتیبانی نمی شود.",
        33 => "تاریخ انقضای کارت سپری شده است",
        34 => "تراکنش اصلی با موفقیت انجام نپذیرفته است.",
        36 => "کارت محدود شده است کارت توسط دستگاه ضبط شود.",
        38 => "تعداد دفعات ورود رمز غلط بیش از حد مجاز است",
        39 => "کارت حساب اعتباری ندارد.",
        40 => "عملیات درخواستی پشتیبانی نمی گردد.",
        41 => "کارت مفقودی می باشد. کارت توسط دستگاه ضبط شود.",
        42 => "کارت حساب عمومی ندارد.",
        43 => "کارت مسروقه می باشد. کارت توسط دستگاه ضبط شود.",
        44 => "کارت حساب سرمایه گذاری ندارد.",
        51 => "موجودی کافی نمی باشد.",
        52 => "کارت حساب جاری ندارد.",
        53 => "کارت حساب قرض الحسنه ندارد.",
        54 => "تاریخ انقضای کارت سپری شده است.",
        55 => "رمز کارت نامعتبر است.",
        56 => "کارت نامعتبر است.",
        57 => "بانک شما این تراکنش را پشتیبانی نمیکند",
        58 => "انجام تراکنش مربوطه توسط پایانه ی انجام دهنده مجاز نمی باشد.",
        59 => "کارت مظنون به تقلب است.",
        61 => "مبلغ تراکنش بیش از حد مجاز می باشد.",
        62 => "کارت محدود شده است.",
        63 => "تمهیدات امنیتی نقض گردیده است.",
        64 => "مبلغ تراکنش اصلی نامعتبر است.( تراکنش مالی اصلی با این مبلغ نمی باشد).",
        65 => " تعداد درخواست تراکنش بیش از حد مجاز می باشد.",
        67 => " کارت توسط دستگاه ضبط شود.",
        75 => " تعداد دفعات ورود رمز غلط بیش از حد مجاز است.",
        77 => " روز مالی تراکنش نا معتبر است.",
        78 => " کارت فعال نیست.",
        79 => " حساب متصل به کارت نامعتبر است یا دارای اشکال است.",
        80 => " تراکنش موفق عمل نکرده است.",
        84 => "بانک صادر کننده کارت پاسخ نمیدهد",
        86 => "موسسه ارسال کننده شاپرک یا مقصد تراکنش در حالت Sign off است.",
        90 => "بانک صادرکننده کارت درحال انجام عملیات پایان روز میباشد",
        91 => "بانک صادر کننده کارت پاسخ نمیدهد",
        92 => "مسیری برای ارسال تراکنش به مقصد یافت نشد. ( موسسه های اع می معتبر نیستند)",
        93 => "تراکنش با موفقیت انجام نشد. (کمبود منابع و نقض موارد قانونی)",
        94 => "ارسال تراکنش تکراری.",
        96 => "بروز خطای سیستمی در انجام تراکنش.",
        97 => " فرایند تغییر کلید برای صادر کننده یا پذیرنده در حال انجام است.",
        98 => " سقف استفاده از رمز ایستا به پایان رسیده است.",
        99 => " خطای صادر کنندگی ",
        200 => " سایر خطاهای نگاشت نشده سامانه های بانتی ",
    );

    public function __construct($errorId)
    {
        $this->errorId = intval($errorId);

        parent::__construct(@self::$errors[$this->errorId].' #'.$this->errorId, $this->errorId);
    }
}