<?php

namespace Hosseinizadeh\Gateway\Novin;

use DateTime;
use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\Novin\NovinException;
use Illuminate\Support\Str;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Novin extends PortAbstract implements PortInterface
{
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://pna.shaparak.ir/ref-payment2/jax/merchantService?wsdl';

    /**
     * order number $reservenum
     *
     * @var string
     */
    protected $reservenum;

    /**
     * login data $WSContext
     *
     * @var array
     */
    protected $WSContext;

    /**
     * Payer Email Address
     *
     * @var string
     */
    protected $email;

    /**
     * Payer Mobile Number
     *
     * @var string
     */
    protected $mobile;


    /**
     * signature
     *
     * @var string
     */
    protected $signature;


    /**
     * uniqueID
     *
     * @var string
     */
    protected $uniqueID;

    public function boot()
    {
        $this->setWSContext();
//        $this->merchantLogin();
    }

    /**
     * Set login data
     * @param $url
     */
    function setWSContext()
    {
        if ($this->config->get('gateway.novin.sessionID')){
            $this->WSContext = [
                'SessionId' => $this->config->get('gateway.novin.sessionID')
            ];
        } else {
            $this->WSContext = [
                'UserId' => $this->config->get('gateway.novin.username'),
                'Password' => $this->config->get('gateway.novin.password'),
            ];
        }

        return $this;
    }

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
        $this->GenerateTransactionDataToSign();
        $this->GenerateSignedDataToken();
        return $this;
    }

    protected function GenerateTransactionDataToSign()
    {
        $fields = array('param' => [
            'WSContext' => $this->WSContext,
            'TransType' => 'EN_GOODS',
            'ReserveNum' => $this->reservenum,
            'Amount' => $this->amount,
            'RedirectUrl' => $this->getCallback(),
        ]);

        if (isset($this->mobile))
            $fields['MobileNo'] = $this->mobile;

        if (isset($this->email))
            $fields['Email'] = $this->email;

        try {
            $soap = new SoapClient($this->serverUrl, ['encoding' => 'UTF-8']);
            $response = $soap->GenerateTransactionDataToSign($fields);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        $code = $response->return->Result;
        if ($code != 'erSucceed') {
            $this->transactionFailed();
            $this->newLog($code, NovinException::$errors[$code]);
            throw new NovinException::$errors[$code];
        }

        if (isset($response->return->DataToSign))
            $this->signature = $response->return->DataToSign;

        if (isset($response->return->UniqueId))
            $this->uniqueID = $response->return->UniqueId;
    }

    protected function GenerateSignedDataToken()
    {
        $fields = array('param' => [
            'WSContext' => $this->WSContext,
            'Signature' => $this->signature,
            'UniqueID' => $this->uniqueID,
        ]);

        try {
            $soap = new SoapClient($this->serverUrl, ['encoding' => 'UTF-8']);
            $response = $soap->GenerateSignedDataToken($fields);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }


        $code = $response->return->Result;
        if ($code != 'erSucceed') {
            $this->transactionFailed();
            $this->newLog($code, NovinException::$errors[$code]);
            throw new NovinException::$errors[$code];
        }

        if (isset($response->return->Token)){
            $this->refId = $response->return->Token;
            $this->transactionSetRefId();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $token = $this->refId;
        return view('gateway::novin-redirector', compact('token'));
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

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.novin.callback-url');

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws NovinException
     */
    protected function userPayment()
    {
        $this->authority = Request('RefNum');
        $status = Request('State');

        if ($status == 'OK') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog('erUnsucceed', NovinException::$errors['erUnsucceed']);
        throw new NovinException('erUnsucceed');
    }

    /**
     * Verify user payment from novin server
     *
     * @return bool
     *
     * @throws NovinException
     */
    protected function verifyPayment()
    {
        $fields = array('param' => [
            'WSContext' => $this->WSContext,
            'Token' => $this->refId,
            'RefNum' => $this->authority,
        ]);

        try {
            $soap = new SoapClient($this->serverUrl, ['encoding' => 'UTF-8']);
            $response = $soap->VerifyMerchantTrans($fields);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        $code = $response->return->Result;
        if ($code != 'erSucceed') {
            $this->transactionFailed();
            $this->newLog($code, NovinException::$errors[$code]);
            throw new NovinException::$errors[$code];
        }

        if (isset($response->return->Amount) && $response->return->Amount == $this->amount){
            $this->trackingCode = $this->authority;
            $this->transactionSucceed();
            $this->newLog($code, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }
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
     * Set Payer Email Address
     *
     * @param $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
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

    protected function merchantLogin()
    {
        $fields = array('param' => array(
            'UserName' => $this->config->get('gateway.novin.username'),
            'Password' => $this->config->get('gateway.novin.password'),
        ));

        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->MerchantLogin($fields);

        } catch(\SoapFault $e) {
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        dd($response);
        if (isset($response->return->SessionID)){
            dd('you can add your sessionID to gateway config, your sessionId is:'.$response->return->SessionID);
            return true;
        }

        $code = $response->return->Result;
        $message = NovinException::$errors[$code];
        throw new \Exception($message);
    }
}
