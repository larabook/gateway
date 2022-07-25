<?php

namespace Hosseinizadeh\Gateway\Novin;

use Hosseinizadeh\Gateway\Exceptions\BankException;

class NovinException extends BankException
{

    public static $errors = array(
        'erSucceed' => 'سرویس با موفقیت اجرا شد.',
        'erUnsucceed' => 'تراکنش ناموفق میباشد',
        'erAAS_UseridOrPassIsRequired' => 'کد کاربری و رمز الزامی هست.',
        'erAAS_InvalidUseridOrPass' => 'کد کاربری و رمز صحیح نمی باشد.',
        'erAAS_InvalidUserType' => 'نوع کاربر نمی باشد.',
        'erAAS_UserExpired' => 'کاربر منقضی شده است.',
        'erAAS_UserNotActive' => 'کاربر غیرفعال است.',
        'erAAS_UserTemporaryInActive' => 'کاربر موقتا غیرفعال شده است.',
        'erAAS_UserSessionGenerateError' => 'خطا در تولید شناسه لاگین',
        'erAAS_UserPassMinLengthError' => 'حداقل طول رمز رعایت نشده است.',
        'erAAS_UserPassMaxLengthError' => 'حداکثر طول رمز رعایت نشده است.',
        'erAAS_InvalidUserCertificate' => 'برای کاربر فایل سرتیفکیت تعریف نشده است.',
        'erAAS_InvalidPasswordChars' => 'کاراکترهای غیرمجاز در رمز',
        'erAAS_InvalidSession' => 'شناسه لاگین معتبر نمی باشد.',
        'erAAS_InvalidChannelId' => 'کانال معتبر نمی باشد.',
        'erAAS_InvalidParam' => 'پارامترها معتبر نمی باشد.',
        'erAAS_NotAllowedToService' => 'کاربر مجوز سرویس را ندارد.',
        'erAAS_SessionIsExpired' => 'شناسه لاگین معتبر نمی باشد.',
        'erAAS_InvalidData' => 'داده ها معتبر نمی باشد.',
        'erAAS_InvalidSignature' => 'امضاء دیتا درست نمی باشد.',
        'erAAS_InvalidToken' => 'توکن معتبر نمی باشد.',
        'erAAS_InvalidSourceIp' => 'آدرس آی پی معتبر نمی باشد.',
        'erMts_ParamIsNull' => 'پارامترهای ورودی خالی می باشد.',
        'erMts_InvalidAmount' => 'مبلغ معتبر نمی باشد.',
        'erMts_InvalidGoodsReferenceIdLen' => 'طول شناسه خرید معتبر نمی باشد.',
        'erMts_InvalidMerchantGoodsReferenceIdLen' => 'طول شناسه خرید پذیرنده معتبر نمی باشد.',
        'erMts_InvalidMobileNo' => 'فرمت شماره موبایل معتبر نمی باشد.',
        'erMts_InvalidRedirectUrl' => 'طول یا فرمت آدرس صفحه رجوع معتبر نمی باشد.',
        'erMts_InvalidReferenceNum' => 'طول یا فرمت شماره رفرنس معتبر نمی باشد.',
        'erMts_InvalidRequestParam' => 'پارامترهای درخواست معتبر نمی باشد.',
        'erMts_InvalidReserveNum' => 'طول یا فرمت شماره رزرو معتبر نمی باشد.',
        'erMts_InvalidSessionId' => 'شناسه لاگین معتبر نمی باشد.',
        'erMts_InvalidSignature' => 'طول یا فرمت امضاء دیتا معتبر نمی باشد.',
        'erMts_InvalidTerminal' => 'کد ترمینال معتبر نمی باشد.',
        'erMts_InvalidToken' => 'توکن معتبر نمی باشد.',
        'erMts_InvalidUniqueId' => 'کد یکتا معتبر نمی باشد.',
        'erScm_InvalidAcceptor' => 'پذیرنده معتبر نمی باشد.',
    );

    public function __construct($errorRef)
    {
        $this->errorRef = $errorRef;

        parent::__construct(@self::$errors[$this->errorRef].' ('.$this->errorRef.')', $this->errorRef);
    }
}
