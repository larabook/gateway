<?php

namespace Larabookir\Gateway\Parsian;

use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Rules\In;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Parsian extends PortAbstract implements PortInterface
{
    /**
     * Url of parsian gateway web service
     *
     * @var string
     */
    protected $serverUrl        = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?wsdl';
    protected $serverUrlConfirm = "https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL";

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

        return \View::make('gateway::parsian-redirector')->with(compact('url'));
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
     * @return bool
     *
     * @throws ParsianErrorException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        $params = array(
            'LoginAccount'   => $this->config->get('gateway.parsian.pin'),
            'Amount'         => $this->amount . "",
            'OrderId'        => $this->transactionId(),
            'CallBackUrl'    => $this->getCallback(),
            'AdditionalData' => ""
        );

        try {
            $soap     = new SoapClient($this->serverUrl);
            $response = $soap->SalePaymentRequest(["requestData" => $params]);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if ($response !== false) {
            $authority = $response->SalePaymentRequestResult->Token;
            $status    = $response->SalePaymentRequestResult->Status;

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
     *
     * @throws ParsianErrorException
     */
    protected function verifyPayment()
    {


        if (!Input::has('Token') && !Input::has('status'))
            throw new ParsianErrorException('درخواست غیر معتبر', -1);

        $authority = Input::get('Token');
        $status    = Input::get('status');

        if ($status != 0) {
            $errorMessage = ParsianResult::errorMessage($status);
            $this->newLog($status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $status);
        }

        if ($this->refId != $authority)
            throw new ParsianErrorException('تراکنشی یافت نشد', -1);

        $params = array(
            'LoginAccount' => $this->config->get('gateway.parsian.pin'),
            'Token'        => $authority,
        );

        try {
            $soap   = new SoapClient($this->serverUrlConfirm);
            $result = $soap->ConfirmPayment([
                "requestData" => $params
            ]);

        } catch (\SoapFault $e) {
            throw new ParsianErrorException($e->getMessage(), -1);
        }

        if ($result === false || !isset($result->ConfirmPaymentResult->Status))
            throw new ParsianErrorException('پاسخ دریافتی از بانک نامعتبر است.', -1);

        if ($result->ConfirmPaymentResult->Status != 0) {
            $errorMessage = ParsianResult::errorMessage($result->ConfirmPaymentResult->Status);
            $this->transactionFailed();
            $this->newLog($result->ConfirmPaymentResult->Status, $errorMessage);
            throw new ParsianErrorException($errorMessage, $result->ConfirmPaymentResult->Status);
        }

        $this->trackingCode = $result->ConfirmPaymentResult->RRN;
        $this->cardNumber   = $result->ConfirmPaymentResult->CardNumberMasked;
        $this->transactionSucceed();
        $this->newLog($result->ConfirmPaymentResult->Status, ParsianResult::errorMessage($result->ConfirmPaymentResult->Status));
    }
}
