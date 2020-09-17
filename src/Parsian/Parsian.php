<?php
namespace Hosseinizadeh\Gateway\Parsian;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;
class Parsian extends PortAbstract implements PortInterface
{
    /**
     * Url of parsian gateway web service
     *
     * @var string
     */
    protected $serverUrl = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?wsdl';
    protected $serverUrlConfirm = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?wsdl';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://pec.shaparak.ir/NewIPG/?Token=';
    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = intval($amount);
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
        $url = $this->gateUrl . $this->refId();
        return view('gateway::parsian-redirector')->with(compact('url'));
    }
    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);
        $this->verifyPayment();
        return $this;
    }

    /**
     * Sets callback url
     * @param $url
     * @return Parsian
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
            $this->callbackUrl = $this->config->get('gateway.parsian.callback-url');
        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * Send pay request to parsian gateway
     *
     * authority  === Token
     * @return bool
     *
     * @throws \SoapFault
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $params = array(
            "requestData" => array(
                'LoginAccount' => $this->config->get('gateway.parsian.pin'),
                'Amount' => $this->amount,
                'OrderId' => $this->transactionId(),
                'CallBackUrl' => $this->getCallback(),
                'AdditionalData' => '',
                // 'authority' => 0,
                // 'status' => 1
            )
        );
        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->SalePaymentRequest($params);
        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }
        if (!isset($response->SalePaymentRequestResult)
            || isset($response->SalePaymentRequestResult)
            && !isset($response->SalePaymentRequestResult->Token)
            || isset($response->SalePaymentRequestResult->Token)
            && $response->SalePaymentRequestResult->Token == '') {
            $errorMessage = ParsianResult::errorMessage($response->SalePaymentRequestResult->Status);
            $this->transactionFailed();
            $this->newLog($response->SalePaymentRequestResult->Status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $response->SalePaymentRequestResult->Status);
        }
        if ($response !== false) {
            $authority = $response->SalePaymentRequestResult->Token;
            $status = $response->SalePaymentRequestResult->Status;
            if ($authority && $status == 0) {
                $this->refId = $authority;
                $this->transactionSetRefId();
                return true;
            }
            $errorMessage = ParsianResult::errorMessage($status);
            $this->transactionFailed();
            $this->newLog($status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $status);
        } else {
            $this->transactionFailed();
            $this->newLog(-1, 'خطا در اتصال به درگاه پارسیان');
            throw new ParsianErrorException('خطا در اتصال به درگاه پارسیان', -1);
        }
    }
    /**
     * Verify payment
     * @authority == Token
     * @throws ParsianErrorException
     */
    protected function verifyPayment()
    {
        if (!Request('status') && !Request('Token')
            || Request('status') && Request('status') == 0
            || Request('Token') && Request('Token') == "")
            throw new ParsianErrorException('درخواست غیر معتبر', -1);
        $authority = Request('Token');
        $status = Request('status');
        if ($status != 0) {
            $errorMessage = ParsianResult::errorMessage($status);
            $this->newLog($status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $status);
        }
        if ($this->refId != $authority)
            throw new ParsianErrorException('تراکنشی یافت نشد', -1);
        $params = array(
            "requestData" => array(
                'LoginAccount' => $this->config->get('gateway.parsian.pin'),
                'Token' => $authority,
                // 'status' => 1
            )
        );
        try {
            $soap = new SoapClient($this->serverUrlConfirm);
            $result = $soap->ConfirmPayment($params);
        } catch (\SoapFault $e) {
            throw new ParsianErrorException($e->getMessage(), -1);
        }
        if ($result === false || !isset($result->ConfirmPaymentResult) || $result->ConfirmPaymentResult == "")
            throw new ParsianErrorException('پاسخ دریافتی از بانک نامعتبر است.', -1);
        if (!isset($result->ConfirmPaymentResult->status)
            || isset($result->ConfirmPaymentResult->status)
            && $result->ConfirmPaymentResult->status != 0
            || !isset($result->ConfirmPaymentResult->RRN)
            || $result->ConfirmPaymentResult->RRN == 0) {
            $errorMessage = ParsianResult::errorMessage($result->ConfirmPaymentResult->status);
            $this->transactionFailed();
            $this->newLog($result->status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $result->ConfirmPaymentResult->status);
        }
        $this->trackingCode = $result->ConfirmPaymentResult->RRN;
        $this->cardNumber = $result->ConfirmPaymentResult->CardNumberMasked;
        $this->transactionSucceed();
        $this->newLog($result->status, ParsianResult::errorMessage($result->status));
    }
}
