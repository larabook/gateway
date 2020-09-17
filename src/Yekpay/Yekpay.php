<?php

namespace Hosseinizadeh\Gateway\Yekpay;

use Hosseinizadeh\Gateway\Enum;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Yekpay extends PortAbstract implements PortInterface
{
    /**
     * Address of verify server
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://gate.yekpay.com/api/payment/verify';

    /**
     * Address of request server
     *
     * @var string
     */
    protected $requestUrl = 'https://gate.yekpay.com/api/payment/request';

    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://gate.yekpay.com/api/payment/start/';

    /**
     * Address exchange
     *
     * @var string
     */
    protected $exchangeUrl = 'https://gate.yekpay.com/api/payment/exchange';

    /**
     * Address exchange
     *
     * @var string
     */
    protected $checkipUrl = 'https://gate.yekpay.com/api/payment/country';

    protected $fromCurrencyCode = 978;
    protected $toCurrencyCode = 978;
    protected $fname = '';
    protected $lname = '';
    protected $email = '';
    protected $mobile = '';
    protected $address = '';
    protected $postalcode = '';
    protected $country = '';
    protected $city = '';
    protected $description = '';

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
     * {@inheritdoc}
     */
    public function redirect()
    {
        return \Redirect::to($this->gateUrl.$this->refId());
    }

    /**
     * {@inheritdoc}
     */
    public function payurl()
    {
        return $this->gateUrl.$this->refId();
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
     * @param $url
     */
    function setCallback($url)
    {
        $this->callbackUrl = $url;
        return $this;
    }

    function setFrom($fromCurrencyCode)
    {
        $this->fromCurrencyCode = $fromCurrencyCode;
        return $this;
    }

    function setTo($toCurrencyCode)
    {
        $this->toCurrencyCode = $toCurrencyCode;
        return $this;
    }

    function setFname($name)
    {
        $this->fname = $name;
        return $this;
    }

    function setLname($name)
    {
        $this->lname = $name;
        return $this;
    }

    function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    function setPostalcode($postalcode)
    {
        $this->postalcode = $postalcode;
        return $this;
    }

    function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.yekpay.callback-url');

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws YekpayException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        $fields = array(
            'merchantId' => $this->config->get('gateway.yekpay.merchantId'),
            'amount' => $this->amount,
            'fromCurrencyCode' => $this->fromCurrencyCode,
            'toCurrencyCode' => $this->toCurrencyCode,
            'orderNumber' => $this->transactionId,
            'callback' => $this->getCallback(),
            'firstName' => $this->fname,
            'lastName' => $this->lname,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'postalCode' => $this->postalcode,
            'country' => $this->country,
            'city' => $this->city,
            'description' => $this->description,
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        if (is_numeric($response['Code']) && $response['Code'] == 100) {
            $this->refId = $response['Authority'];
            $this->transactionSetRefId();
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response['Code'], YekpayException::$errors[$response['Code']]);
        throw new YekpayException($response['Code']);
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws YekpayException
     */
    protected function userPayment()
    {
        $status = Request('success');

        if ($status == 1) {
            return true;
        }

        $this->transactionFailed();
        $this->newLog(-30, YekpayException::$errors[-30]);
        throw new YekpayException(-30);
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws YekpayException
     */
    protected function verifyPayment()
    {
        $fields = [
            'merchantId' => $this->config->get('gateway.yekpay.merchantId'),
            'authority'  => $this->refId(),
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        if ($response['Code'] == 100) {
            $this->trackingCode = $response['Tracking'];
            $this->transactionSucceed();
            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response['Code'], YekpayException::$errors[ $response['Code'] ]);
        throw new YekpayException($response['Code']);
    }

    /**
     * exchange
     *
     * @return bool
     *
     * @throws YekpayException
     */
    public function exchange($from, $to)
    {
        $fields = [
            'merchantId' => $this->config->get('gateway.yekpay.merchantId'),
            'fromCurrencyCode'  => $from,
            'toCurrencyCode'  => $to,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->exchangeUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        if ($response['Code'] == 100) {
            return $response['Rate_up'];
        }

        throw new YekpayException($response['Code']);
    }


    /**
     * checkip
     *
     * @return bool
     *
     * @throws YekpayException
     */
    public function checkip($ip)
    {
        $fields = [
            'ip'  => $ip,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->checkipUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        if ($response['Code'] == 100) {
            return true;
        }

        return false;
    }
}
