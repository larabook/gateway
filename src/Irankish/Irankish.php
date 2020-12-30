<?php

namespace Larabookir\Gateway\Irankish;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Irankish extends PortAbstract implements PortInterface
{

    protected $irankishDB;

    public function __construct()
    {
        $this->irankishDB = DB::table('irankish')->first();
    }

    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://ikc.shaparak.ir/XToken/Tokens.xml';
//    protected $serverUrl = 'http://banktest.ir/gateway/irankishToken/ws?wsdl';
    protected $serverVerifyUrl = "https://ikc.shaparak.ir/XVerify/Verify.xml";
//    protected $serverVerifyUrl = "http://banktest.ir/gateway/irankishVerify/ws?wsdl";

//    protected $gateUrl = "http://banktest.ir/gateway/irankish/gate";
    protected $gateUrl = "https://ikc.shaparak.ir/TPayment/Payment/index";

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
        $gateUrl = $this->gateUrl;
        $token = $this->refId;
        $merchantId = $this->irankishDB->merchantId;

        return view('gateway::irankish-redirector')->with(compact('token', 'merchantId', 'gateUrl'));
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
        if (!$this->callbackUrl) {
            $this->callbackUrl = $this->irankishDB->callbackUrl;
        }

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws IranKishException
     */
    protected function sendPayRequest()
    {
        $dateTime = new DateTime();

        $this->newTransaction();

        $fields = [
            'amount' => $this->amount,
            'merchantId' => $this->irankishDB->merchantId,
            'invoiceNo' => $this->transactionId(),
            'paymentId' => $this->getCustomInvoiceNo(),
            'revertURL' => $this->getCallback(),
            'description' => $this->getCustomDesc(),
        ];

        try {
            $soap = new SoapClient($this->serverUrl, ['soap_version' => SOAP_1_1]);
            $response = $soap->MakeToken($fields);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if ($response->MakeTokenResult->result != true) {
            $this->transactionFailed();
            $this->newLog($response->MakeTokenResult->message, IrankishException::$errors[$response[0]]);
            throw new IranKishException($response[0]);
        }
        $this->refId = $response->MakeTokenResult->token;
        $this->transactionSetRefId();
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws IranKishException
     */
    protected function userPayment()
    {

        $this->refId = Request::input('token');
        $this->trackingCode = Request::input('referenceId');
        if (Request::has('cardNo'))
            $this->cardNumber = Request::input('cardNo');
        $payRequestResCode = Request::input('resultCode');

        if ($payRequestResCode == '100') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($payRequestResCode, @IrankishException::$errors[$payRequestResCode]);
        throw new IrankishException($payRequestResCode);
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws IranKishException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $fields = [
            'token' => $this->refId(),
            'merchantId' => $this->irankishDB->merchantId,
            'referenceNumber' => $this->trackingCode(),
            'sha1key' => $this->irankishDB->sha1key
        ];

        try {
            $soap = new SoapClient($this->serverVerifyUrl);
            $response = $soap->KicccPaymentsVerification($fields);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if ($response->KicccPaymentsVerificationResult != $this->amount) {
            $this->transactionFailed();
            $this->newLog($response->KicccPaymentsVerificationResult, IrankishException::$errors[$response->KicccPaymentsVerificationResult]);
            throw new IrankishException($response->KicccPaymentsVerificationResult);
        }

        $this->transactionSucceed();
        $this->newLog($response->KicccPaymentsVerificationResult, Enum::TRANSACTION_SUCCEED_TEXT);


        return true;
    }

}
