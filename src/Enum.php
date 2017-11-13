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
