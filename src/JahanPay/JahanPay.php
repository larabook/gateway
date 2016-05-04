<?php

namespace Larabookir\Gateway\JahanPay;

use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class JahanPay extends PortAbstract implements PortInterface
{
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'http://www.jahanpay.com/webservice?wsdl';

    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'http://www.jahanpay.com/pay_invoice/';

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = ($amount / 10);

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
        return header('Location: '.$this->gateUrl.$this->refId());
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
     * Send pay request to server
     *
     * @return void
     *
     * @throws JahanPayException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->requestpayment(
                $this->config->get('gateway.jahanpay.api'),
                $this->amount,
                $this->buildQuery($this->config->get('gateway.jahanpay.callback-url'), array('transaction_id' => $this->transactionId())),
                $this->transactionId(),
                ''
            );

        } catch(\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if (intval($response) >= 0) {
            $this->refId = $response;
            $this->transactionSetRefId($this->transactionId);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response, JahanPayException::$errors[$response]);
        throw new JahanPayException($response);
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws JahanPayException
     */
    protected function userPayment()
    {
        $refId = @$_GET['au'];

        if ($this->refId() != $refId) {
            $this->transactionFailed();
            $this->newLog(-30, JahanPayException::$errors[-30]);
            throw new JahanPayException(-30);
        }

        return true;
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws JahanPayException
     */
    protected function verifyPayment()
    {
        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->verification(
                $this->config->get('gateway.jahanpay.api'),
                $this->amount,
                $this->refId
            );

        } catch(\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if (intval($response) == 1) {
            $this->transactionSucceed();
            $this->newLog($response, self::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response, JahanPayException::$errors[$response]);
        throw new JahanPayException($response);
    }
}
