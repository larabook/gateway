<?php

namespace Larabookir\Gateway\Asanpardakht;

use Illuminate\Support\Facades\Input;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Asanpardakht extends PortAbstract implements PortInterface
{
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://services.asanpardakht.net/paygate/merchantservices.asmx?wsdl';

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

        return view('gateway::asan-pardakht-redirector')->with([
            'refId' => $this->refId
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->userPayment();
        $this->verifyAndSettlePayment();
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
            $this->callbackUrl = $this->config->get('gateway.asanpardakht.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws AsanPardakhtException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        $username = $this->config->get('gateway.asanpardakht.username');
        $password = $this->config->get('gateway.asanpardakht.password');
        $orderId = $this->transactionId();
        $price = $this->amount;
        $localDate = date("Ymd His");
        $additionalData = "";
        $callBackUrl = $this->getCallback();
        $req = "1,{$username},{$password},{$orderId},{$price},{$localDate},{$additionalData},{$callBackUrl},0";

        $encryptedRequest = $this->encrypt($req);
        $params = array(
            'merchantConfigurationID' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'encryptedRequest' => $encryptedRequest
        );

        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->RequestOperation($params);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }


        $response = $response->RequestOperationResult;
        $responseCode = explode(",", $response)[0];
        if ($responseCode != '0') {
            $this->transactionFailed();
            $this->newLog($response, AsanPardakhtException::getMessageByCode($response));
            throw new AsanPardakhtException($response);
        }
        $this->refId = substr($response, 2);
        $this->transactionSetRefId();
    }


    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws AsanPardakhtException
     */
    protected function userPayment()
    {
        $ReturningParams = Input::get('ReturningParams');
        $ReturningParams = $this->decrypt($ReturningParams);

        $paramsArray = explode(",", $ReturningParams);
        $Amount = $paramsArray[0];
        $SaleOrderId = $paramsArray[1];
        $RefId = $paramsArray[2];
        $ResCode = $paramsArray[3];
        $ResMessage = $paramsArray[4];
        $PayGateTranID = $paramsArray[5];
        $RRN = $paramsArray[6];
        $LastFourDigitOfPAN = $paramsArray[7];


        $this->trackingCode = $PayGateTranID;
        $this->cardNumber = $LastFourDigitOfPAN;
        $this->refId = $RefId;


        if ($ResCode == '0' || $ResCode == '00') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($ResCode, $ResMessage . " - " . AsanPardakhtException::getMessageByCode($ResCode));
        throw new AsanPardakhtException($ResCode);
    }


    /**
     * Verify and settle user payment from bank server
     *
     * @return bool
     *
     * @throws AsanPardakhtException
     * @throws SoapFault
     */
    protected function verifyAndSettlePayment()
    {

        $username = $this->config->get('gateway.asanpardakht.username');
        $password = $this->config->get('gateway.asanpardakht.password');

        $encryptedCredintials = $this->encrypt("{$username},{$password}");
        $params = array(
            'merchantConfigurationID' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'encryptedCredentials' => $encryptedCredintials,
            'payGateTranID' => $this->trackingCode
        );


        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->RequestVerification($params);
            $response = $response->RequestVerificationResult;

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if ($response != '500') {
            $this->transactionFailed();
            $this->newLog($response, AsanPardakhtException::getMessageByCode($response));
            throw new AsanPardakhtException($response);
        }


        try {

            $response = $soap->RequestReconciliation($params);
            $response = $response->RequestReconciliationResult;

            if ($response != '600')
                $this->newLog($response, AsanPardakhtException::getMessageByCode($response));

        } catch (\SoapFault $e) {
            //If fail, shaparak automatically do it in next 12 houres.
        }


        $this->transactionSucceed();

        return true;
    }


    /**
     * @param string $string
     * @param int $blocksize
     * @return string
     */
    private function addpadding($string, $blocksize = 32)
    {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }


    /**
     * @param string $string
     * @return string|boolean
     */
    private function strippadding($string)
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }


    /**
     * Encrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function encrypt($string = "")
    {

        $key = $this->config->get('gateway.asanpardakht.key');
        $iv = $this->config->get('gateway.asanpardakht.iv');

        try {

            $soap = new SoapClient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL");
            $params = array(
                'aesKey' => $key,
                'aesVector' => $iv,
                'toBeEncrypted' => $string
            );

            $response = $soap->EncryptInAES($params);
            return $response->EncryptInAESResult;

        } catch (\SoapFault $e) {
            return "";
        }
    }


    /**
     * Decrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function decrypt($string = "")
    {
        $key = $this->config->get('gateway.asanpardakht.key');
        $iv = $this->config->get('gateway.asanpardakht.iv');

        try {

            $soap = new SoapClient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL");
            $params = array(
                'aesKey' => $key,
                'aesVector' => $iv,
                'toBeDecrypted' => $string
            );

            $response = $soap->DecryptInAES($params);
            return $response->DecryptInAESResult;

        } catch (\SoapFault $e) {
            return "";
        }
    }

}