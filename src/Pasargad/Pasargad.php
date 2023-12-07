<?php

namespace Larabookir\Gateway\Pasargad;

use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\Parsian\ParsianErrorException;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;
use Larabookir\Gateway\Models\Gateway;


class Pasargad extends PortAbstract implements PortInterface
{
    /**
     * Url of parsian gateway web service
     *
     * @var string
     */

    protected $checkTransactionUrl = 'https://pep.shaparak.ir/CheckTransactionResult.aspx';
    protected $verifyUrl = 'https://pep.shaparak.ir/VerifyPayment.aspx';
    protected $refundUrl = 'https://pep.shaparak.ir/doRefund.aspx';

    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://pep.shaparak.ir/gateway.aspx';

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
    public function ready($payment_id, $callback_url)
    {
        $this->sendPayRequest($payment_id, $callback_url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {

        $processor = new RSAProcessor(json_decode(Gateway::pasargad()->connection_info)->certificate_path, RSAKeyType::XMLFile);

        $url = $this->gateUrl;
        $redirectUrl = $this->getCallback();
        $invoiceNumber = $this->transactionId();
        $amount = $this->amount;
        $terminalCode = json_decode(Gateway::pasargad()->connection_info)->terminalId;
        $merchantCode = json_decode(Gateway::pasargad()->connection_info)->merchantId;
        $timeStamp = date("Y/m/d H:i:s");
        $invoiceDate = date("Y/m/d H:i:s");
        $action = 1003;
        $data = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $redirectUrl . "#" . $action . "#" . $timeStamp . "#";
        $data = sha1($data, true);
        $data = $processor->sign($data); // امضاي ديجيتال
        $sign = base64_encode($data); // base64_encode

        return view('gateway::pasargad-redirector')->with(compact('url', 'redirectUrl', 'invoiceNumber', 'invoiceDate', 'amount', 'terminalCode', 'merchantCode', 'timeStamp', 'action', 'sign'));
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
            $this->callbackUrl = Gateway::pasargad()->callback_url;

        return $this->callbackUrl;
    }

    /**
     * Send pay request to parsian gateway
     *
     * @return bool
     *
     * @throws ParsianErrorException
     */
    protected function sendPayRequest($payment_id, $callback_url)
    {
        $this->newTransaction($payment_id, $callback_url);
    }

    /**
     * Verify payment
     *
     * @throws ParsianErrorException
     */
    protected function verifyPayment()
    {
        $fields = array(
            'invoiceUID' => Request::input('tref'),
        );

        $result = Parser::post2https($fields, $this->checkTransactionUrl);
        $array = Parser::makeXMLTree($result);
        $verifyResult = $this->callVerifyPayment($array);
        $array['result'] = $verifyResult['result'] ?? false;


        if ($array['result'] != "True") {
            $this->newLog(-1, Enum::TRANSACTION_FAILED_TEXT);
            $this->transactionFailed();
            throw new PasargadErrorException(Enum::TRANSACTION_FAILED_TEXT, -1);
        }

        $this->refId = $array['transactionReferenceID'];
        $this->transactionSetRefId();

        $this->trackingCode = $array['traceNumber'];
        $this->transactionSucceed();
        $this->newLog($array['result'], Enum::TRANSACTION_SUCCEED_TEXT);
    }

    /**
     * @param $data
     * @return array
     */
    protected function callVerifyPayment($data)
    {
        $processor = new RSAProcessor(json_decode(Gateway::pasargad()->connection_info)->certificate_path, RSAKeyType::XMLFile);
        $merchantCode = json_decode(Gateway::pasargad()->connection_info)->merchantId;
        $terminalCode = json_decode(Gateway::pasargad()->connection_info)->terminalId;
        $invoiceNumber = $data['invoiceNumber'];
        $invoiceDate = $data['invoiceDate'];
        $timeStamp = date("Y/m/d H:i:s");
        $amount = $data['amount'];
        $signData = "#" . $merchantCode . "#" . $terminalCode . "#" . $invoiceNumber . "#" . $invoiceDate . "#" . $amount . "#" . $timeStamp . "#";
        $signDataSha1 = sha1($signData, true);
        $tempSign = $processor->sign($signDataSha1);
        $sign = base64_encode($tempSign);

        $body = [
            'merchantCode' => $merchantCode,
            'terminalCode' => $terminalCode,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'amount' => $amount,
            'timeStamp' => $timeStamp,
            'sign' => $sign
        ];


        return $this->convertXMLtoArray(Parser::post2https($body, $this->verifyUrl));
    }

    /**
     * @param string $xmlString
     * @return array
     */
    private function convertXMLtoArray($xmlString)
    {
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);

        return json_decode($json,True);
    }
}
