<?php

namespace Larabookir\Gateway\Sadad;

class SadadResult
{
    const ERROR_CONNECT = -2542;
    const ERROR_CONNECT_MESSAGE = 'unable_to_connect';

    const INVALID_RESPONSE_CODE = -2541;
    const INVALID_RESPONSE_MESSAGE = 'invalid_response';

    const UNKNOWN_CODE = -2540;
    const UNKNOWN_MESSAGE = 'unknown';

    private static $results = array(
        array(
            'code' => SadadResult::ERROR_CONNECT,
            'message'=>SadadResult::ERROR_CONNECT_MESSAGE,
            'fa' => 'خطا در اتصال به درگاه سداد',
            'en' => 'Error in connect to sadad',
            'retry' => false
        ),
        array(
            'code' => SadadResult::INVALID_RESPONSE_CODE,
            'message'=>SadadResult::INVALID_RESPONSE_MESSAGE,
            'fa' => 'جواب نامعتبر',
            'en' => 'Invalid Response',
            'retry' => false
        ),
        array(
            'code' => -1,
            'message'=>'not_set',
            'fa' => 'نتیجه استعلام نامعلوم و یا کاربر از انجام تراکنش منصرف شده است.',
            'en' => 'Result of the Inquiry Unknown or Customer Cancellation',
            'retry' => true,
        ),
        array(
            'code' => -1,
            'message'=>'not_exist',
            'fa' => 'پارامترهای ارسالی صحیح نیست و یا تراکنش در سیستم وجود ندارد.',
            'en' => 'Parameters passed is incorrect or there is no transaction in the system.',
            'retry' => false,
        ),
        array(
            'code' => 9000,
            'message'=>'incomplet_info',
            'fa' => 'درخواست تراکنش در سیستم ثبت شده ولی تراکنش هنوز شروع نشده است.',
            'en' => 'Transaction request registered in the system, but the transaction has not started yet.',
            'retry' => true,
        ),
        array(
            'code' => 9001,
            'message'=>'wait_for_send_and_get_response',
            'fa' => 'تراکنش در سیستم ثبت شده و پیام مالی به سیستم بانک ارسال شده است اما هنوز پاسخی دریافت نشده است.',
            'en' => 'Financial transaction log and the message has been sent to the banking system but not yet received.',
            'retry' => true,
        ),
        array(
            'code' => 9004,
            'message'=>'wait_for_reversal',
            'fa' => 'سیستم در حال تلاش جهت برگشت تراکنش خرید است.',
            'en' => 'The system is trying to buy back transaction.',
            'retry' => true,
        ),
        array(
            'code' => 9005,
            'message'=>'wait_for_reversal_advise',
            'fa' => 'سیستم در حال تلاش جهت برگشت تراکنش خرید است.',
            'en' => 'The system is trying to buy back transaction.',
            'retry' => true,
        ),
        array(
            'code' => 9006,
            'message'=>'reversaled',
            'fa' => 'تراکنش ناموفق بوده و مبلغ با موفقیت به حساب مشتری برگشت خورده است',
            'en' => 'Purchase operation was unsuccessful and The amount is returned successfully to the client`s account',
            'retry' => false,
        ),
        array(
            'code' => 0,
            'message'=>'failed',
            'fa' => 'عملیات خرید نا موفق بوده است',
            'en' => 'Purchase operation was unsuccessful',
            'retry' => false,
        ),
        array(
            'code' => 56,
            'message'=>'failed',
            'fa' => 'کارت نامعتبر است.',
            'en' => 'Card Not Effective',
            'retry' => false,
        ),
        array(
            'code' => 51,
            'message'=>'failed',
            'fa' => 'مبلغ درخواستی از موجودی حساب شما, بیشتر است.حداقل مانده حساب شما پس از عملیات پرداخت باید 100,000 ریال باشد.',
            'en' => 'Inventory shortage',
            'retry' => false,
        ),
        array(
            'code' => 55,
            'message'=>'failed',
            'fa' => 'رمز کارت صحیح نمی باشد لطفا مجددا, سعی کنید.',
            'en' => 'Incorrect Pin',
            'retry' => false,
        ),
        array(
            'code' => 9008,
            'message'=>'check_status',
            'fa' => 'در هنگام انجام عملیات CheckStatus مشکلی رخ داده است.',
            'en' => 'CheckStatus operations when a problem has occurred.',
            'retry' => true,
        ),
        array(
            'code' => 9009,
            'message'=>'async_get_response',
            'fa' => 'سیستم در حال تلاش برای دریافت جواب عملیات بانکی است.',
            'en' => 'The system is trying to Get answers to banking operations.',
            'retry' => true,
        ),
        array(
            'code' => 9010,
            'message'=>'wait_for_reversal_auto',
            'fa' => 'تراکنش به لیست تراکنش های برگشت خودکار اضافه شده است و در انتظار برگشت می باشد.',
            'en' => 'The transaction is automatically added to the list of back transactions and is expected to return.',
            'retry' => true,
        ),
        array(
            'code' => 9011,
            'message'=>'pendingcurrenttransaction',
            'fa' => 'تراکنش برگشت خرید ارسال شده است اما پاسخ دریافت شده قطعی نیست و تراکنش در لیست تراکنش های معوق می باشد و نتیجه عملیات بانکی ظرف 24 ساعت آینده مشخص خواهد شد.',
            'en' => 'Back purchase transaction has been sent but not conclusive answer has been received and the transaction is on the list of pending transactions and banking operations will be determined within the next 24 hours.',
            'retry' => false,
        ),
        array(
            'code' => 9012,
            'message'=>'pendingtransactionreversed',
            'fa' => 'تراکنش ناموفق - نتیجه تراکنش معوق بعد از 24 ساعت مشخص می شود',
            'en' => 'Failed Transaction - result of outstanding transactions will be determined after 24 hours',
            'retry' => false,
        ),
        array(
            'code' => 2,
            'message'=>'request_done',
            'fa' => 'تراکنش موفق',
            'en' => 'Successful Transaction',
            'retry' => false,
        ),
        array(
            'code' => 0,
            'message'=>'pendingtransactioncommited',
            'fa' => 'تایید پرداخت - نتیجه تراکنش معوق بعد از 24 ساعت مشخص می شود',
            'en' => 'Verify Payment - result of outstanding transactions will be determined after 24 hours',
            'retry' => false,
        ),
        array(
            'code' => 0,
            'message'=>'commit',
            'fa' => 'تایید پرداخت',
            'en' => 'Verify Payment',
            'retry' => false,
        ),
    );

    /**
     * return response
     *
     * @param int $code
     * @param string $message
     * @return null
     */
    public static function codeResponse($code,$message)
    {
        $code = intval($code);

        foreach(self::$results as $v) {
            if ($v['message'] == $message && $v['code'] == $code)
                return $v;
        }

        return null;
    }
}
