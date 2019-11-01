<?php

namespace Larabookir\Gateway;

class Enum
{
	const MELLAT = 'MELLAT';
	const SADAD = 'SADAD';
	const ZARINPAL = 'ZARINPAL';
	const PAYLINE = 'PAYLINE';
	const JAHANPAY = 'JAHANPAY';
	const PARSIAN = 'PARSIAN';
	const PASARGAD = 'PASARGAD';
	const SAMAN = 'SAMAN';
	const ASANPARDAKHT = 'ASANPARDAKHT';
	const PAYPAL = 'PAYPAL';
  	const PAYIR = 'PAYIR';
  	const IRANKISH = 'IRANKISH';
  	const MASKAN = self::IRANKISH;

  	static function getIPGs(){

        $reflect = new \ReflectionClass(static::class);
        $excepts=[
            'MASKAN',
            'TRANSACTION_INIT',
            'TRANSACTION_INIT_TEXT',
            'TRANSACTION_SUCCEED',
            'TRANSACTION_SUCCEED_TEXT',
            'TRANSACTION_FAILED',
            'TRANSACTION_FAILED_TEXT',
        ];
        
        if(function_exists('array_except'))
            return array_values(array_except($reflect->getConstants(),$excepts));
        else
            return array_values(\Illuminate\Support\Arr::except($reflect->getConstants(),$excepts));
    }

	/**
	 * Status code for status field in poolport_transactions table
	 */
	const TRANSACTION_INIT = 'INIT';
	const TRANSACTION_INIT_TEXT = 'تراکنش ایجاد شد.';

	/**
	 * Status code for status field in poolport_transactions table
	 */
	const TRANSACTION_SUCCEED = 'SUCCEED';
	const TRANSACTION_SUCCEED_TEXT = 'پرداخت با موفقیت انجام شد.';

	/**
	 * Status code for status field in poolport_transactions table
	 */
	const TRANSACTION_FAILED = 'FAILED';
	const TRANSACTION_FAILED_TEXT = 'عملیات پرداخت با خطا مواجه شد.';

}
