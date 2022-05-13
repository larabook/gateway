<?php
namespace Larabookir\Gateway\RayanPay;

use App\Models\Card;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class RayanPay extends PortAbstract implements PortInterface
{

    protected $order_id;
    protected $link;
    /*
     * مشخصات مربوط درگاه پرداخت
     * با اطلاعات سرویس که از رایان پی گرفته می شود پر شود
     */
    public $username = 'MarketingClient1717';
    public $password = 'MrsKm3h1jklOsi26EPwsSH6W';
    public $clientId = '023a0452-1970-465f-8bee-0ea8b204e4b2';

    public function __construct()
    {
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        $this->request();

        return $this;
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl) {
            $this->callbackUrl = 'callback/rayanpay/{transaction_id}';
        }

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $link = $this->link;

        return redirect($link);
    }

    public function request()
    {
			
        $username = $this->username;
        $password = $this->password;
        $clientId = $this->clientId;
        $referenceId = hexdec(uniqid());

        //exit();
        //$mobile = 98 . substr($this->get_order_mobile(), 1);


        $ch = curl_init('https://pms.rayanpay.com/api/v1/auth/token/generate');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'clientId' => $clientId,
            'userName' => $username,
            'password' => $password,
        )));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json_decoded = json_decode($result, true);
        if ($http_code != 200 || is_array($json_decoded)) {
            return $this->errors($http_code, 'token', ($json_decoded['ErrDesc'] ? 'خطایی در هنگام اتصال به درگاه رخ داده است.' : ''));
        }

        $token = $result;
        $this->set_stored("$referenceId", $token);
        /*
         * کد انحصاری که برای تمام طول پرداخت لازم هست
         */
        $_SESSION["SaleReferenceId"] = $referenceId;
        $ch = curl_init('https://pms.rayanpay.com/api/v1/ipg/payment/start');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'amount' => (int)$this->amount,
            'callbackUrl' => $this->getCallback(),
            'referenceId' => $referenceId,
            'msisdn' => '',
            'gatewayId' => 100,
            'gateSwitchingAllowed' => true,
        )));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        /*
         * کد پیگیری بانک
         */
        $paymentId = $result["paymentId"];
	
        if (!empty($result['bankRedirectHtml'])) {
            //file_put_contents(getcwd() . "/bankRedirectHtml.txt", $result['bankRedirectHtml']);
            /*
             * انتقال به درگاه پرداخت
             */
            print_r($result['bankRedirectHtml']);
            return;
        }
        /*
         * بروز خطا عدم انتقال
         */
        if (!empty($result['errors'])) {
            $error = json_encode($result['errors']);
        } else {
            $error = $result['error'] ? 'خطای ناشناخته رخ داده است.' : '';
        }

        return $this->errors($http_code, 'payment_start', $error);
    }

    protected function get_stored($key)
    {
        //این را در بانک داده نگه داری کنید
        /*
         * مثلا MYSql
         */
        return file_get_contents(getcwd() . "/token.txt");
    }

    protected function set_stored($key, $val)
    {
        //این را در بانک داده نگه داری کنید
        /*
 * مثلا MYSql
 */
        return file_put_contents(getcwd() . "/token.txt", $val);
    }
    /*
     * تایید یا عدم تایید به بانک
     */
    public function verify($transaction)
    {

        parent::verify($transaction);

        $referenceId = $_SESSION["SaleReferenceId"];
        $token = $this->get_stored("$referenceId");

        $ch = curl_init('https://pms.rayanpay.com/api/v1/ipg/payment/response/parse');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'referenceId' => $referenceId,
            'header' => '',
            'content' => http_build_query($_POST)
        )));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!empty($result['paymentId']) && !empty($result['hashedBankCardNumber'])) {
            $status = 'completed';
            $error = '';
            /*
             * کدهایی که مربوط به موفقیت در انجام کار بوده
             */
        } else {
            $status = 'failed';
            if (!empty($result['ErrorDesc'])) {
                $error = $result['ErrorDesc'];
            } else if (!empty($result['errors'])) {
                $error = json_encode($result['errors']);
            } else {
                $error = $result['error'] ? 'تراکنش ناموفق بوده است.' : '';
            }
            $error = $this->errors($http_code, 'payment_parse', $error);
            /*
             * کدهای عدم موفقیت پرداخت
             */
        }

        $transaction_id = $referenceId;


        return compact('status', 'transaction_id', 'error');
    }

    public function errors($error, $method, $prepend = '')
    {
        if ($method == 'token') {
            switch ($error) {

                case '400' :
                    $message = 'نقص در پارامترهای ارسالی';
                    break;

                case '401' :
                    $message = 'کد کاربری/رمز عبور /کلاینت/آی پی نامعتبر است';
                    break;
            }
        } elseif ($method == 'payment_start') {
            switch ($error) {

                case '401' :
                    $message = 'توکن نامعتبر';
                    break;

                case '601' :
                    $message = 'اتصال به درگاه خطا دارد (پرداخت ناموفق)';
                    break;
            }

        } elseif ($method == 'payment_parse') {
            switch ($error) {

                case '401' :
                    $message = 'توکن نامعتبر است';
                    break;

                case '601' :
                    $message = 'پرداخت ناموفق';
                    break;

                case '600' :
                    $message = 'پرداخت در حالت Pending می باشد و باید متد fullfill برای تعیین وضعیت صدا زده شود';
                    break;

                case '602' :
                    $message = 'پرداخت یافت نشد';
                    break;

                case '608' :
                    $message = 'قوانین پرداخت یافت نشد (برای پرداخت هایی که قوانین دارند)';
                    break;

                case '609' :
                    $message = 'وضعیت پرداخت نامعتبر میباشد';
                    break;
            }
        }

		return $message;
       // return implode(' :: ', array_filter(array($prepend, $message ? '' : '')));
    }
}
