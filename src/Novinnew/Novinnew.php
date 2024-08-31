<?php

namespace Hosseinizadeh\Gateway\Novinnew;

use DateTime;
use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\Novinnew\NovinnewException;
use Illuminate\Support\Str;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Novinnew extends PortAbstract implements PortInterface
{
    /**
     * Address of main rest server
     *
     * @var string
     */

    protected $serverUrl = 'https://pna.shaparak.ir/mhipg/api/Payment/';

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount * 10;

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
        $token = $this->refId;
        return \Redirect::to('https://pna.shaparak.ir/mhui/home/index/' . $token);
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
     * @param $url
     * @return $this|string
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
            $this->callbackUrl = $this->config->get('gateway.novinnew.callback-url');
        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
        return $url;
    }

    /**
     * @return bool
     * @throws NovinnewException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $orderId = $this->transactionId();

        $data = [
            'CorporationPin' => $this->config->get('gateway.novinnew.CorporationPin'),
            'Amount' => $this->amount,
            'OrderId' => $orderId,
            'AdditionalData' => $this->getCustomDesc(),
            'CallBackUrl' => $this->getCallback(),
        ];

        $objectRequest = json_encode($data);

        try {
            $response = $this->clientsPost($this->serverUrl . "NormalSale", 'POST', $objectRequest);
            if ($response->status == 0) {
                $this->refId = $response->token;
                $this->transactionSetRefId();
                return true;
            }
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }
        $this->transactionFailed();
        $this->newLog($response->status, NovinnewException::$errors[$response->status]);
        throw new NovinnewException($response->status);
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws NovinnewException
     */
    protected function userPayment()
    {
        $this->authority = Request('Token');

        if ($this->authority) {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($status, NovinnewException::$errors[80]);
        throw new NovinnewException(80);
    }

    /**
     * Verify user payment from Novin server
     *
     * @return bool
     *
     * @throws NovinnewException
     */
    protected function verifyPayment()
    {
        $data = [
            'CorporationPin' => $this->config->get('gateway.novinnew.CorporationPin'),
            'Token' => $this->authority,
        ];

        $objectRequest = json_encode($data);

        try {
            $response = $this->clientsPost($this->serverUrl . "confirm", 'POST', $objectRequest);
            if ($response->status == 0) {
                $this->trackingCode = $response->rrn;
                $this->transactionSucceed();
                $this->newLog($response->status, Enum::TRANSACTION_SUCCEED_TEXT);
                return true;
            }
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }

        $this->transactionFailed();
        $this->newLog($response->status, NovinnewException::$errors[$response->status]);
        throw new NovinnewException($response->status);
    }


    /**
     * @param $url
     * @param $methods
     * @param array $options
     * @param array $headers
     * @return array|bool|int|mixed|string
     */
    private function clientsPost($url, $methods, $data = array())
    {
        try {
            $headers = [
                "Content-Type: application/json",
            ];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CUSTOMREQUEST => $methods,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);

            return $response;

        } catch (\Exception $e) {
            //$err = curl_error($curl);
            $response = $e->getCode();
        }

        return $response;
    }

    /**
     * Set Description
     *
     * @param $reservenum
     * @return void
     */
    public function setReserveNum($reservenum)
    {
        $this->reservenum = $reservenum;
    }

    /**
     * Set Payer Mobile Number
     *
     * @param $mobile
     * @return void
     */
    public function setMobile($mobile)
    {
        if (strlen($mobile) === 12 && Str::startsWith($mobile, '98')) {
            $mobile = Str::replaceFirst('98', '0', $mobile);
        }

        if (strlen($mobile) === 10 && Str::startsWith($mobile, '9')) {
            $mobile = '0'.$mobile;
        }

        $this->mobile = $mobile;
    }

    /**
     * Set Payer Email Address
     *
     * @param $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
