<?php

namespace Larabookir\Gateway\Bahamta;

use Larabookir\Gateway\Exceptions\BankException;

class BahamtaException extends BankException {
    public static $errors = array (
        'NOT_AUTHORIZED'       => 'گرچه ساختار کلید درست است، اما هیچ فروشنده‌ای با این کلید در webpay ثبت نشده است.' ,
        'INVALID_AMOUNT'       => 'پارامتر مبلغ فرستاده نشده و یا نادرست فرستاده شده است. مثلاً ممکن است ارقام مبلغ سه رقم به سه رقم با علامت , جدا شده باشند که نادرست است.' ,
        'TOO_LESS_AMOUNT'      => 'مبلغ کمتر از حد مجاز (هزار تومان) است.' ,
        'TOO_MUCH_AMOUNT'      => 'مبلغ بیشتر از حد مجاز (پنجاه میلیون تومان) است.' ,
        'INVALID_REFERENCE'    => 'شماره شناسه پرداخت ناردست است. این مقدار باید یک عبارت حرفی با طول بین ۱ تا ۶۴ حرف باشد.' ,
        'INVALID_TRUSTED_PAN'  => 'لیست شماره کارتها نادرست است.' ,
        'INVALID_CALLBACK'     => 'آدرس فراخوانی نادرست است. این آدرس باید با ://http و یا ://https شروع شود.' ,
        'INVALID_PARAM'        => 'خطایی در مقادیر فرستاده شده وجود دارد که جزو موارد یاد شده بالا نیست.' ,
        'ALREADY_PAID'         => 'درخواست پرداختی با شناسه داده شده قبلاً ثبت و پرداخت شده است.' ,
        'MISMATCHED_DATA'      => ' درخواست پرداختی با شناسه داده شده قبلاً ثبت و منتظر پرداخت است، اما مقادیر فرستاده شده در این درخواست، با درخواست اصلی متفاوت است.' ,
        'NO_REG_TERMINAL'      => 'ترمینالی برای این فروشنده ثبت نشده است.' ,
        'NO_AVAILABLE_GATEWAY' => 'درگاههای پرداختی که این فروشنده در آنها ترمینال ثبت شده دارد، قادر به ارائه خدمات نیستند.' ,
        'SERVICE_ERROR'        => 'خطای داخلی سرویس رخ داده است.' ,


        'INVALID_API_CALL' => 'قالب فراخوانی سرویس رعایت نشده است.' ,
        'INVALID_API_KEY'  => 'لید الکترونیکی صاحب فروشگاه فرستاده نشده و یا ساختار آن نادرست است.' ,
        'UNKNOWN_BILL'     => 'پرداختی با شماره شناسه فرستاده شده ثبت نشده است.' ,
        'MISMATCHED_DATA'  => 'مبلغ اعلام شده با آنچه در webpay ثبت شده است مطابقت ندارد.' ,
        'NOT_CONFIRMED'    => 'این پرداخت تأیید نشد.' ,
    );

    public function __construct( $errorId ) {
        $this->errorId = intval( $errorId );

        parent::__construct( @self::$errors[ $this->errorId ] . ' #' . $this->errorId , $this->errorId );
    }
}
