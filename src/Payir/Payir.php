<?php
namespace Larabookir\Gateway\Payir;

use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Payir extends PortAbstract implements PortInterface
{
    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'https://pay.ir/payment/send';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://pay.ir/payment/verify';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://pay.ir/payment/gateway/';


    protected $factorNumber;

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
        return redirect()->to($this->gateUrl . $this->refId);
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
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.payir.callback-url');
        return urlencode($this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]));
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws PayirSendException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $fields = [
            'api'      => $this->config->get('gateway.payir.api'),
            'amount'   => $this->amount,
            'redirect' => $this->getCallback(),
        ];

        if (isset($this->factorNumber))
            $fields['factorNumber'] = $this->factorNumber;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);
        if (is_numeric($response['status']) && $response['status'] > 0) {
            $this->refId = $response['transId'];
            $this->transactionSetRefId();
            return true;
        }
        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirSendException::$errors[ $response['errorCode'] ]);
        throw new PayirSendException($response['errorCode']);
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws PayirReceiveException
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
        throw new PayirReceiveException(-5);
    }

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws PayirReceiveException
     */
    protected function verifyPayment()
    {
        $fields = [
            'api'     => $this->config->get('gateway.payir.api'),
            'transId' => $this->refId(),
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);
        if ($response['status'] == 1) {
            $this->transactionSucceed();
            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirReceiveException::$errors[ $response['errorCode'] ]);
        throw new PayirReceiveException($response['errorCode']);
    }
}
