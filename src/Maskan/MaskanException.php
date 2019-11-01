<?php

namespace Larabookir\Gateway\Maskan;

use Larabookir\Gateway\Exceptions\BankException;

class MaskanException extends BankException
{
    public function __construct($errorId)
    {
        $this->errorId = $errorId;

        parent::__construct(@self::$errors[$errorId] . ' #' . $errorId, $errorId);
    }

    public static $errors = [
        '505' => 'خطای نامشخص',
        '-1'  => 'کلید نامعتبر است',
        '0'   => 'Success موفقیت ',
        '1'   => 'صادرکننده ی کارت از انجام تراکنش صرف نظر کرد.',
        '2'   => 'عملیات تاییدیه این تراکنش قبلا با موفقیت صورت پذیرفته است.',
        '3'   => 'پذیرنده ی فروشگاهی نامعتبر است.',
        '5'   => 'از انجام تراکنش صرف نظر شد.',
        '6'   => 'بروز خطا',
        '7'   => 'به دلیل شرایط خاص کارت توسط دستگاه ضبط شود.',
        '8'   => 'باتشخیص هویت دارنده ی کارت، تراکنش موفق می باشد.',
        '9'   => 'در حال حاضر امکان پاسخ دهی وجود ندارد',
        '12'  => 'تراکنش نامعتبر است.',
        '13'  => 'مبلغ تراکنش اصلاحیه نادرست است.',
        '14'  => 'شماره کارت ارسالی نامعتبر است. (وجود ندارد)',
        '15'  => 'صادرکننده ی کارت نامعتبراست.(وجود ندارد)',
        '16'  => 'تراکنش مورد تایید است و اطلاعات شیار سوم کارت به روز رسانی شود.',
        '19'  => 'تراکنش مجدداً ارسال شود.',
        '20'  => 'خطای ناشناخته از سامانه مقصد',
        '23'  => 'کارمزد ارسالی پذیرنده غیر قابل قبول است.',
        '25'  => 'شماره شناسایی صادرکننده غیر معتبر',
        '30'  => 'قالب پیام دارای اشکال است.',
        '31'  => 'پذیرنده توسط سوئیچ پشتیبانی نمی شود.',
        '33'  => 'تاریخ انقضای کارت سپری شده است.',
        '34'  => 'دارنده کارت مظنون به تقلب است.',
        '36'  => 'کارت محدود شده است.کارت توسط دستگاه ضبط شود.   ',
        '38'  => 'تعداد دفعات ورود رمز غلط بیش از حدمجاز است.    ',
        '39'  => 'کارت حساب اعتباری ندارد.  ',
        '40'  => ' عملیات درخواستی پشتیبانی نمی گردد. ',
        '41'  => 'کارت مفقودی می باشد.   ',
        '42'  => 'کارت حساب عمومی ندارد. ',
        '43'  => 'کارت مسروقه می باشد. ',
        '44'  => ' کارت حساب سرمایه گذاری ندارد. ',
        '48'  => 'تراکنش پرداخت قبض قبلا انجام پذیرفته',
        '51'  => ' موجودی کافی نیست.  ',
        '52'  => ' کارت حساب جاری ندارد. ',
        '53'  => ' کارت حساب قرض الحسنه ندارد. ',
        '56'  => ' تاریخ انقضای کارت سپری شده است. 54 55 Pin -Error  کارت نا معتبر است.',
        '57'  => 'انجام تراکنش مربوطه توسط دارنده ی کارت مجاز نمی باشد.   ',
        '58'  => 'انجام تراکنش مربوطه توسط پایانه ی انجام دهنده مجاز نمی باشد. ',
        '59'  => 'کارت مظنون به تقلب است.  ',
        '61'  => 'مبلغ تراکنش بیش از حد مجاز است.  ',
        '62'  => ' کارت محدود شده است.   ',
        '63'  => ' تمهیدات امنیتی نقض گردیده است.   ',
        '64'  => 'مبلغ تراکنش اصلی نامعتبر است.(تراکنش مالی اصلی با این مبلغ نمی باشد)    ',
        '65'  => 'تعداد درخواست تراکنش بیش از حد مجاز است.    ',
        '67'  => 'کارت توسط دستگاه ضبط شود.  ',
        '75'  => ' تعداد دفعات ورود رمزغلط بیش از حد مجاز است.',
        '77'  => 'روز مالی تراکنش نا معتبر است.',
        '78'  => 'کارت فعال نیست.  ',
        '79'  => ' حساب متصل به کارت نامعتبر است یا دارای اشکال است.  ',
        '80'  => ' خطای داخلی سوییچ رخ داده است  ',
        '81'  => 'خطای پردازش سوییچ  ',
        '83'  => ' نموده است.Sign Offارائه دهنده خدمات پرداخت یا سامانه شاپرک اعلام  ',
        '84'  => ' Host -Down  ',
        '86'  => 'Sign offموسسه ارسال کننده، شاپرک یا مقصد تراکنش در حالت  ',
        '90'  => 'سامانه مقصد تراکنش درحال انجام عملیات پایان روز می باشد. ',
        '91'  => 'پاسخی از سامانه مقصد دریافت نشد   ',
        '92'  => 'مسیری برای ارسال تراکنش به مقصد یافت نشد. (موسسه های اعلامی معتبر نیستند)  ',
        '93'  => 'پیام دوباره ارسال گردد. (درپیام های تاییدیه)    ',
        '94'  => ' پیام تکراری است  ',
        '96'  => ' بروز خطای سیستمی در انجام تراکنش  ',
        '97'  => ' مبلغ تراکنش غیر معتبر است ',
        '98'  => ' شارژ وجود ندارد.   ',
        '99'  => ' تراکنش غیر معتبر است یا کلید ها هماهنگ نیستند ',
        '100' => 'خطای نامشخص',
        '500' => ' کدپذیرندگی معتبر نمی باشد    ',
        '501' => 'مبلغ بیشتر از حد مجاز است   ',
        '502' => ' نام کاربری و یا رمز ورود اشتباه است ',
        '503' => ' آی پی دامنه کار بر نا معتبر است  ',
        '504' => ' آدرس صفحه برگشت نا معتبر است ',
        '506' => 'شماره سفارش تکراری است -  و یا مشکلی دیگر در درج اطلاعات ',
        '507' => 'خطای اعتبارسنجی مقادیر  ',
        '508' => 'فرمت درخواست ارسالی نا معتبر است',
        '509' => 'قطع سرویس های شاپرک',
        '510' => 'لغو درخواست توسط خود کاربر',
        '511' => 'طولانی شدن زمان تراکنش و عدم انجام در زمان مقرر توسط کاربر',
        '512' => ' کارتCvv2خطا اطلاعات',
        '513' => 'خطای اطلاعات تاریخ انقضاء کارت',
        '514' => ' خطا در رایانامه درج شده',
        '515' => ' خطا در کاراکترهای کپچا',
        '516' => ' اطلاعات درخواست نامعتبر میباشد',
        '517' => 'خطا در شماره کارت',
        '518' => 'تراکنش مورد نظر وجود ندارد.',
        '519' => 'مشتری از پرداخت منصرف شده است',
        '520' => 'مشتری در زمان مقرر پرداخت را انجام نداده است',
        '521' => 'قبلا درخواست تائید با موفقیت ثبت شده است',
        '522' => ' قبلا درخواست اصلاح تراکنش با موفقیت ثبت شده است',
        '600' => 'لغو تراکنش',

    ];
}