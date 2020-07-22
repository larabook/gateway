<?php

namespace Hosseinizadeh\Gateway\Asanpardakht;

use Hosseinizadeh\Gateway\Enum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Mockery\Exception;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Asanpardakht extends PortAbstract implements PortInterface
{
    /**
     * Address of main SOAP server
     *
     * @var string
     */

    protected $serverUrl = 'https://rest.asanpardakht.net/v1/';

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
        $RefId = $this->refId;
        return view('gateway::asan-pardakht-redirector', compact('RefId'));
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        //   parent::verify($transaction);

        $this->transactionId = $transaction;
        $resultCheckTransaction = $this->checkTransaction($transaction);
        if (isset($resultCheckTransaction['status']) && $resultCheckTransaction['status'] == 200) {
            $jsonDecode = json_decode($resultCheckTransaction['result']);
            if (isset($jsonDecode->payGateTranID)) {

                $this->rrn = $jsonDecode->rrn;
                $this->cardNumber = $jsonDecode->cardNumber;
                $salesOrderID = $jsonDecode->salesOrderID;
                $this->trackingCode = $jsonDecode->payGateTranID;

                $find = $this->getTable()->whereId($transaction)
                    ->where(['price' => $jsonDecode->amount, 'ref_id' => $jsonDecode->refID])
                    ->first();

                if (isset($find) && $find) {

                    $update = $find->update([
                        'tracking_code' => $jsonDecode->payGateTranID,
                        'card_number' => $jsonDecode->cardNumber,
                    ]);

                    $resultVerify = $this->userPayment($jsonDecode->payGateTranID);

                    if ($resultVerify['status'] == 200) {
                        return true;
                    } else {
                        $this->transactionFailed();
                        $this->newLog($resultVerify, AsanpardakhtException::getMessageByCodeVerify($resultVerify));
                        throw new AsanpardakhtException($resultVerify,true);
                    }
                }
            }
        }

        // $this->verifyAndSettlePayment();
        $this->transactionFailed();
        $this->newLog(472, AsanpardakhtException::getMessageByCode(472));
        throw new AsanpardakhtException(472);
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
     * @throws AsanpardakhtException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $username = $this->config->get('gateway.asanpardakht.username');
        $password = $this->config->get('gateway.asanpardakht.password');
        $orderId = $this->transactionId();
        $price = $this->amount;

        $Time = $this->getTime();
        $Time = trim($Time, '"');
        $localDate = $Time;

        $additionalData = $this->getCustomDesc();
        $callBackUrl = $this->getCallback();

        $data = [
            'merchantConfigurationId' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'serviceTypeId' => 1,
            'localInvoiceId' => $orderId,
            'amountInRials' => $this->amount,
            'localDate' => $localDate,
            'additionalData' => $this->getCustomDesc(),
            'callbackURL' => isset($this->callbackUrl) ? $this->callbackUrl . "/?factor=" . $orderId : Enum::CALL_BACK_URL_ASANPARDAKHT . "/?factor=" . $orderId,
            'paymentId' => '0',
            'settlementPortions' => [
                [
                    'iban' => $this->config->get('gateway.asanpardakht.iban'),
                    'amountInRials' => $this->amount
                ]
            ],
        ];
        $objectRequest = json_encode($data);

        try {

            $results = $this->clientsPost($this->serverUrl . 'Token', 'POST', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'usr' => $username,
                    'pwd' => $password,
                ],
                'body' => $objectRequest
            ]);

            if (isset($results) && !is_int($results)) {

                $this->refId = $results;
                $this->transactionSetRefId();
                return true;
            }

        } catch (\Exception $e) {

            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }

        $this->transactionFailed();
        $this->newLog($results, AsanpardakhtException::getMessageByCode($results));
        throw new AsanpardakhtException($results);
    }


    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws AsanpardakhtException
     */
//    protected function userPayment()
//    {
//        $ReturningParams = Input::get('ReturningParams');
//        //$ReturningParams = $this->decrypt($ReturningParams);
//
//        $paramsArray = explode(",", $ReturningParams);
//        $Amount = $paramsArray[0];
//        $SaleOrderId = $paramsArray[1];
//        $RefId = $paramsArray[2];
//        $ResCode = $paramsArray[3];
//        $ResMessage = $paramsArray[4];
//        $PayGateTranID = $paramsArray[5];
//        $RRN = $paramsArray[6];
//        $LastFourDigitOfPAN = $paramsArray[7];
//
//
//        $this->trackingCode = $PayGateTranID;
//        $this->cardNumber = $LastFourDigitOfPAN;
//        $this->refId = $RefId;
//
//
//        if ($ResCode == '0' || $ResCode == '00') {
//            return true;
//        }
//
//        $this->transactionFailed();
//        $this->newLog($ResCode, $ResMessage . " - " . AsanpardakhtException::getMessageByCode($ResCode));
//        throw new AsanpardakhtException($ResCode);
//    }


    protected function userPayment($payGateTranId)
    {
        $data = [
            'merchantConfigurationId' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'payGateTranId' => $payGateTranId
        ];
        $objectRequest = json_encode($data);
        $result = "";
        try {
            $result = $this->clientsPost($this->serverUrl . "Verify", "POST", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'usr' => $this->config->get('gateway.asanpardakht.username'),
                    'pwd' =>$this->config->get('gateway.asanpardakht.password'),
                ],
                'body' => $objectRequest,
            ]);

            if ($result) {
                return [
                    'status' => 200,
                    'result' => $result
                ];
            }

        } catch (Exception $e) {
            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }

        $this->transactionFailed();
        $this->newLog($result, AsanpardakhtException::getMessageByCode($result));
        throw new AsanpardakhtException($result);
    }


    /**
     * Verify and settle user payment from bank server
     *
     * @return bool
     *
     * @throws AsanpardakhtException
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
            $this->newLog($response, AsanpardakhtException::getMessageByCode($response));
            throw new AsanpardakhtException($response);
        }


        try {

            $response = $soap->RequestReconciliation($params);
            $response = $response->RequestReconciliationResult;

            if ($response != '600')
                $this->newLog($response, AsanpardakhtException::getMessageByCode($response));

        } catch (\SoapFault $e) {
            //If fail, shaparak automatically do it in next 12 houres.
        }


        $this->transactionSucceed();

        return true;
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

    private function getTime()
    {
        $this->setPortName(Enum::ASANPARDAKHT);
        $result = $this->clientsPost($this->serverUrl . "Time", "GET");
        return $result;
    }

    public function checkTransaction($value)
    {
        if ($value) {
            try {
                $result = $this->clientsPost($this->serverUrl . "TranResult?MerchantConfigurationId=" . $this->config->get('gateway.asanpardakht.merchantConfigId') . "&LocalInvoiceId=" . $value . "", "GET", [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'usr' => $this->config->get('gateway.asanpardakht.username'),
                        'pwd' => $this->config->get('gateway.asanpardakht.password'),
                    ],
                ]);

                if ($result) {
                    return [
                        'status' => 200,
                        'result' => $result
                    ];
                }

            } catch (Exception $e) {
                $this->transactionFailed();
                $this->newLog('httpResponse', $e->getMessage());
                throw $e;
            }
        }
        $this->transactionFailed();
        $this->newLog(472, AsanpardakhtException::getMessageByCode(472));
        throw new AsanpardakhtException(472);
    }

}
