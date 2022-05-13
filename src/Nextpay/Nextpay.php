<?php

namespace Larabookir\Gateway\Nextpay;

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

class Nextpay extends PortAbstract implements PortInterface
{

    protected $order_id;
    protected $link;
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://nextpay.org/nx/gateway/token';

    protected $paymentUrl = 'https://nextpay.org/nx/gateway/payment';

    protected $verifyUrl = 'https://nextpay.org/nx/gateway/verify';


//    public function __construct( $order_id ) {
//        $this->order_id = $order_id;
//    }

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
        $this->sendPayRequest();

        return $this;
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws NextpayException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        $params = array(
            'api_key' => $this->config->get('gateway.nextpay.api-token'),
            'amount' => (int)$this->amount,
            'order_id' => $this->order_id,
            'callback_uri' => $this->getCallback(),
        );

        $response = Http::post($this->serverUrl, $params);
        $response = $response->object();

        $trans_id = $response->trans_id;
        $code = $response->code;
        if ($code != -1) {
            $this->transactionFailed();
            $this->newLog($code, NextpayException::$errors[$code]);
            throw new NextpayException($code);
        }

        $this->refId = $trans_id;
        $this->link = $this->paymentUrl . '/' . $trans_id;
        $this->trackingCode = $trans_id;

        $this->transactionSetRefId();
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl) {
            $this->callbackUrl = $this->config->get('gateway.nextpay.callback-url');
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

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->userPayment();
        $this->verifyPayment();

        return $this;
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws MellatException
     */
    protected function userPayment()
    {
        $payRequestResCode = Request::get('status');
        $this->trackingCode = Request::get('transaction_id');
        $this->refId = Request::get('trans_id');
        $this->order_id = Request::get('order_id');
//        $this->amount       = Input::get( 'amount' );
        $this->cardNumber = Request::get('card_holder');
//
        if (isset($this->cardNumber) && $this->cardNumber != '0000-0000-0000-0000') {
            $this->validateCardNumber();
            return true;
        }

//        $this->transactionFailed();
//        $this->newLog( $payRequestResCode , NextpayException::$errors[ $payRequestResCode ] );
//        throw new NextpayException( $payRequestResCode );
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws NextpayException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $params = array(
            'trans_id' => $this->refId,
            'api_key' => $this->config->get('gateway.nextpay.api-key'),
            'order_id' => $this->order_id,
            'amount' => (int)$this->amount,
        );
        $response = Http::post($this->verifyUrl, $params);
        $response = $response->object();

        $code = $response->code;
        if ($code != 0) {
            $this->transactionFailed();
            $this->newLog($code, NextpayException::$errors[$code]);
            throw new NextpayException($code);
        } else {
            $this->trackingCode = $this->refId;
            $this->transactionSucceed();
            $this->newLog('SUCCESS', Enum::TRANSACTION_SUCCEED_TEXT);

            return true;
        }
    }

    public function validateCardNumber()
    {
        if (auth()->check()) {
            $userCards = auth()->user()->cards->where('status', 1)->pluck('last_number');
//            $userCards = Card::where('user_id',auth()->id())->where( 'status' , 1 )->pluck( 'last_number' );
            if ($userCards) {
                if (!$userCards->contains(substr($this->cardNumber, -4))) {
                    $this->transactionFailed();
                    $this->newLog(4444, NextpayException::$errors[4444]);
                    throw new NextpayException(4444);
                    $this->cancelPayment();
                }
            } else {
                $this->transactionFailed();
                $this->newLog(4444, NextpayException::$errors[4444]);
                throw new NextpayException(4444);
            }
        }
    }

    protected function cancelPayment()
    {
        $params = array(
            'trans_id' => $this->refId,
            'api_key' => $this->config->get('gateway.nextpay.api-key'),
            'amount' => (int)$this->amount,
            'refund_request' => 'yes_money_back',
        );
        $response = Http::post($this->verifyUrl, $params);
        $response = $response->object();

        $code = $response->code;

        $this->transactionFailed();
        $this->newLog($code, NextpayException::$errors[$code]);
        throw new NextpayException($code);
        return;
    }

    /**
     * Sets callback url
     *
     * @param $url
     */
    function setCallback($url)
    {
        $this->callbackUrl = $url;

        return $this;
    }

    /**
     * Send settle request
     *
     * @return bool
     *
     * @throws MellatException
     * @throws SoapFault
     */
    protected function settleRequest()
    {

        $params = array(
            'id' => $this->refId,
            'order_id' => $this->order_id,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->settelUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->config->get('gateway.idpay.api-key') . '',
            'X-SANDBOX: 1',
        ));

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        var_dump($httpcode);
        var_dump($result);
    }
}
