<?php

namespace Larautility\Gateway\Parsian;


class ParsianResult
{

	public static $errors = array(
		0 => 'تراکنش با موفقیت انجام شد.',
		1 => 'خطا در انجام تراکنش',
		2 => 'بین عملیات وقفه افتاده است.',
		10 => 'شماره کارت نامعتبر است.',
		11 => 'کارت شما منقضی شده است',
		12 => 'رمز کارت وارد شده اشتباه است',
		13 => 'موجودی کارت شما کافی نیست',
		14 => 'مبلغ تراکنش بیش از سقف مجاز پذیرنده است.',
		15 => 'سقف مجاز روزانه شما کامل شده است.',
		18 => 'این تراکنش قبلا تایید شده است',
		20 => 'اطلاعات پذیرنده صحیح نیست.',
		21 => 'invalid authority',
		22 => 'اطلاعات پذیرنده صحیح نیست.',
		30 => 'عملیات قبلا با موفقیت انجام شده است.',
		34 => 'شماره تراکنش فروشنده درست نمی باشد.',
		-113 => 'پارامتر ورودی و یا برخی از خصوصیات آن خالی است و یا مقداردهی نشده است',
		-127 => 'آدرس IP معتبر نمی باشد',
		-32768 => 'خطای ناشناخته رخ داده است.',
		-1552 => 'برگشت تراکنش مجاز نمی باشد',
		-1551 => 'برگشت تراکنش قبلا انجام شده است.',
		-1550 => 'برگشت تراکنش در وضعیت جاری امکان پذیر نمی باشد.',
		-1549 => 'زمان مجاز برای درخواست برگشت تراکنش به تمام رسیده است.',
		-1540 => 'تایید تراکنش ناموفق می باشد.',
		-1533 => 'تراکنش قبلا تایید شده است.',
		-1532 => 'تراکنش از سوی پذیرنده تایید شد.',
		-1531 => 'تایید تراکنش ناموفق امکان پذیر نمی باشد.',
		-1530 => 'پذیرنده مجاز به تایید این تراکنش نمی باشد.',
		-1528 => 'اطلاعات پرداخت یافت نشد.',
		-1527 => 'انجام عملیات درخواست پرداخت تراکنش.',
		-138 => 'عملیات پرداخت توسط کاربر لغو شد.',
		-131 => 'توکن نامعتبر می باشد.',
		-130 => 'زمان توکن منقضی شده است',
	);

	public static function errorMessage($errorId)
	{
		return isset(self::$errors[$errorId]) ? self::$errors[$errorId] : $errorId;
	}
}
