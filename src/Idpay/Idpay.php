<?php
namespace Larabookir\Gateway\Idpay;

use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Idpay extends PortAbstract implements PortInterface
{
    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'https://api.idpay.ir/v1.1/payment';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://api.idpay.ir/v1.1/payment/verify';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://api.idpay.ir/v1.1/payment/verify';


    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount * 10;
        return $this;
    }

    /**
     * تعیین شماره فاکتور (اختیاری)
     *
     * @param $factorNumber
     *
     * @return $this
     */
    public function setFactorNumber($factorNumber)
    {
        $this->factorNumber = $factorNumber;
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
     * {@inheritdoc}
     */
    public function redirect()
    {
        return redirect()->to($this->gateUrl);
    }

    /**
     * {@inheritdoc}
     */

    public function setConfig($config)
    {
        parent::setConfig($config);
        $this->header = array(
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->config->get('gateway.idpay.api'),
            'X-SANDBOX: ' . $this->config->get('gateway.idpay.sandbox'),
        );
    }

    public function verify($transaction)
    {
        parent::verify($transaction);
        $this->userPayment();
        $this->verifyPayment();
        return $this;
    }

    /**
     * Sets callback url
     *
     * @param $url
     */
    public function setCallback($url)
    {
        $this->callbackUrl = $url;
        return $this;
    }

    /**
     * Gets callback url
     * @return string
     */
    public function getCallback()
    {
        if (!$this->callbackUrl) {
            $this->callbackUrl = $this->config->get('gateway.idpay.callback-url');
        }
        return urlencode($this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]));
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws IdpaySendException
     */
    protected function sendPayRequest()
    {
        if (is_null($this->factorNumber)) {
            throw new IdpaySendException(32);
        }

        $this->newTransaction();
        
        $fields = array(
            'order_id'      => $this->factorNumber,
            'amount'        => $this->amount,
            'callback'      =>  $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId() , 'order_id' => $this->factorNumber]),
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
          
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        
        
        if (isset($response['link'])) {
            $this->gateUrl = $response['link'];
            $this->refId = $response['id'];
            $this->transactionSetRefId();
            return true;
        }
        
        if (isset($response['status']) && $response['status'] > 0) {
            $this->refId = $response['transId'];
            $this->transactionSetRefId();
            return true;
        }
    
    
        $this->transactionFailed();
        $this->newLog($response['error_code'], IdpaySendException::$errors[ $response['error_code'] ]);
        throw new IdpaySendException($response['error_code']);
    }

    public function setCustomDesc($description)
    {
        $this->description = $description;
    }

    

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws IdpayReceiveException
     */
    protected function userPayment()
    {
        $status = Request::input('status');
        $transId = Request::input('transId');
        $this->cardNumber = Request::input('cardNumber');
        $message = Request::input('message');
        if (is_numeric($status) && $status > 0) {
            $this->trackingCode = $transId;
            return true;
        }
        $this->transactionFailed();
        $this->newLog(-5, $message);
        throw new IdpayReceiveException(-5);
    }

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws IdpayReceiveException
     */
    protected function verifyPayment()
    {
        $fields = array(
            'id'        =>  $this->refId,
            'order_id'  =>  request()->order_id,
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
          
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        if (isset($response['status']) &&  $response['status'] == 100) {
            $this->refId        = $response['id'];
            $this->cardNumber   = $response['payment']['card_no'];
            $this->trackingCode = $response['payment']['track_id'];

            $this->transactionSucceed();
            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        if (isset($response['error_code'])) {
            $this->transactionFailed();
            $this->newLog($response['error_code'], IdpayReceiveException::$errors[ $response['error_code'] ]);
            throw new IdpayReceiveException($response['error_code']);
        }
    }
}
