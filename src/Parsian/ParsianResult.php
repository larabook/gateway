<?php

namespace Masihjazayeri\Gateway\Parsian;


class ParsianResult
{
    public static $errors
        = [
            -32768 => "خطاي ناشناخته رخ داده است",
            -32768 => "خطاي ناشناخته رخ داده است",
            -1552  => "برگشت تراکنش مجاز نمی باشد",
            -1551  => "برگشت تراکنش قب ًلا انجام شده است",
            -138   => "عملیات پرداخت توسط کاربر لغو شد",
            0      => 'تراکنش با موفقیت انجام شد.',
            1      => 'خطا در انجام تراکنش',
            2      => 'بین عملیات وقفه افتاده است.',
            10     => 'شماره کارت نامعتبر است.',
            11     => 'کارت شما منقضی شده است',
            12     => 'رمز کارت وارد شده اشتباه است',
            13     => 'موجودی کارت شما کافی نیست',
            14     => 'مبلغ تراکنش بیش از سقف مجاز پذیرنده است.',
            15     => 'سقف مجاز روزانه شما کامل شده است.',
            18     => 'این تراکنش قبلا تایید شده است',
            20     => 'اطلاعات پذیرنده صحیح نیست.',
            21     => 'invalid authority',
            22     => 'اطلاعات پذیرنده صحیح نیست.',
            30     => 'عملیات قبلا با موفقیت انجام شده است.',
            34     => 'شماره تراکنش فروشنده درست نمی باشد.',
        ];

	public static function errorMessage($errorId)
	{
		return isset(self::$errors[$errorId]) ? self::$errors[$errorId] : $errorId;
	}
}
