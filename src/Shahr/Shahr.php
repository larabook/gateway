<?php

namespace Larabookir\Gateway\Shahr;

use Carbon\Carbon;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Shahr extends PortAbstract implements PortInterface
{
    /**
     *
     * @var Array $optional_data An array of optional data
     *  that will be sent with the payment request
     *
     */
    protected $optional_data = [];

    /**
     * @var Session ID from login
     */
    private $session_id;

    /**
     * @var Int
     */
    private $return_amount;

    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $gateUrl = "https://fcp.shaparak.ir/_ipgw_/";

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
        $this->newTransaction();

        return $this;
    }

    /**
     *
     * Add optional data to the request
     *
     * @param Array $data an array of data
     *
     */
    function setOptionalData (Array $data)
    {
        $this->optional_data = $data;
    }


    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $main_data = [
            'amount'        => $this->amount,
            'merchant'      => $this->config->get('gateway.shahr.merchantId'),
            'resNum'        => $this->transactionId(),
            'callBackUrl'   => $this->getCallback()
        ];

        $data = array_merge($main_data, $this->optional_data);

        return \View::make('gateway::shahr-redirector')->with($data)->with('gateUrl',$this->gateUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);
        $this->login();
        $this->verifyTransaction();

        return $this;
    }

    /**
     * Login
     * @throws \Exception
     */
    private function login()
    {
        try {
            $info = new \StdClass();
            $info->username = $this->config->get('gateway.shahr.username');
            $info->password = $this->config->get('gateway.shahr.password');

            /** @var $client */
            $client = new \nusoap_client('https://fcp.shaparak.ir/ref-payment/jax/merchantAuth?wsdl',true);
            $str = $client->call("login", array('loginRequest' => $info));
            $this->session_id = ($str['return']);

            /** Throw error if empty session id */
            if(empty($this->session_id))
            {
                $this->transactionFailed();
                $status = '-1';
                $this->newLog($status, @ShahrException::$errors[$status]);
                throw new ShahrException($status);
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }

    }

    /**
     * Verify Transaction
     */
    private function verifyTransaction()
    {
        try {
            $State = request('State');
            $this->trackingCode = request('TraceNo');
            $this->transactionId = request('ResNum');
            $this->refId = request('RefNum');

            /** @var $contextinfo */
            $contextinfo = new \stdClass();
            $contextinfo->data = new \stdClass();
            $contextinfo->data->entry = array('key'=>'SESSION_ID','value'=> $this->session_id);

            /** @var $requestinfo */
            $requestinfo = new \StdClass();
            $requestinfo->refNumList = $this->refId;

            /** @var $client */
            $client = new \nusoap_client('https://fcp.shaparak.ir/ref-payment/jax/merchantAuth?wsdl',true);
            $str1 = $client->call("verifyTransaction", array('context' => $contextinfo,'verifyRequest' => $requestinfo));

            /** If cancelled by user */
            if($State == 'Canceled By User')
            {
                $this->transactionFailed();
                $this->newLog(substr($State,0,10), @ShahrException::$errors[$State]);
                throw new ShahrException($State);
            }

            /** Check Errors */
            if(isset($str1['return']) and is_array($str1['return']) and isset($str1['return']['verifyResponseResults']['verificationError']))
            {
                $VerificationError = ($str1['return']['verifyResponseResults']['verificationError']);
                $this->transactionFailed();
                $this->reverseTransaction();
                $this->newLog(substr($VerificationError,0,10), @ShahrException::$errors[$VerificationError]);
                throw new ShahrException($VerificationError);
            }

            /** Update transaction */
            $this->getTable()->whereId($this->transactionId)->update([
                'ref_id' => $this->refId,
                'tracking_code' => $this->trackingCode,
                'updated_at' => Carbon::now(),
            ]);

            /** Success transaction */
            if ($State == 'OK') {
                $this->transactionSucceed();
                return true;
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * Reversing transaction
     */
    private function reverseTransaction()
    {
        /** @var $contextinfo */
        $contextinfo = new \stdClass();
        $contextinfo->data = new \stdClass();
        $contextinfo->data->entry = array('key'=>'SESSION_ID','value'=> $this->session_id);

        /** @var $reverseinfo */
        $reverseinfo = new \stdClass();
        $reverseinfo->amount = intval(request('transactionAmount'));
        $reverseinfo->mainTransactionRefNum = request('RefNum');
        $reverseinfo->reverseTransactionResNum = request('ResNum');

        /** @var $client */
        $client = new \nusoap_client('https://fcp.shaparak.ir/ref-payment/jax/merchantAuth?wsdl',true);
        $str1 = $client->call("reverseTransaction", array('context' => $contextinfo,'reverseRequest' => $reverseinfo));
        $Refreverse = ($str1['return']['refNum']);
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
            $this->callbackUrl = $this->config->get('gateway.shahr.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }
}
