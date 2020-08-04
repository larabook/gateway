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
     * Address of main rest server
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
        parent::verify($transaction);

        $this->transactionId = $transaction->id;
        $resultCheckTransaction = $this->checkTransaction($transaction->id);
        if (isset($resultCheckTransaction['status']) && $resultCheckTransaction['status'] == 200) {
            $jsonDecode = json_decode($resultCheckTransaction['result']);
            if (isset($jsonDecode->payGateTranID)) {

                $this->rrn = $jsonDecode->rrn;
                $this->cardNumber = $jsonDecode->cardNumber;
                $salesOrderID = $jsonDecode->salesOrderID;
                $this->trackingCode = $jsonDecode->payGateTranID;

                $find = $this->getTable()->whereId($transaction->id)
                    ->where(['price' => $jsonDecode->amount, 'ref_id' => $jsonDecode->refID])
                    ->first();

                $resultVerify = [
                    'code' => 471
                ];
                if (isset($find) && $find) {

                    $find->update([
                        'tracking_code' => $jsonDecode->payGateTranID,
                        'card_number' => $jsonDecode->cardNumber,
                    ]);

                    $resultVerify = $this->userPayment($jsonDecode->payGateTranID);

                    if ($resultVerify['status'] == 200) {
                        return true;
                    } else {
                        $this->transactionFailed();
                        $this->newLog($resultVerify['status'], AsanpardakhtException::getMessageByCodeVerify($resultVerify['status']));
                        throw new AsanpardakhtException($resultVerify, true);
                    }
                }
            }
        }

        $this->transactionFailed();
        $this->newLog($resultVerify['code'], AsanpardakhtException::getMessageByCodeVerify($resultVerify['code']));
        throw new AsanpardakhtException($resultVerify, true);
    }

    /**
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
            $this->callbackUrl = $this->config->get('gateway.asanpardakht.callback-url');
        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
        return $url;
    }

    /**
     * @return bool
     * @throws AsanpardakhtException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $orderId = $this->transactionId();
        $price = $this->amount;

        $Time = $this->getTime();

        if ($Time == false) {
            return false;
        }

        $this->username = $this->config->get('gateway.asanpardakht.username');
        $this->password = $this->config->get('gateway.asanpardakht.password');

        $Time = trim($Time, '"');
        $localDate = $Time;

        $additionalData = $this->getCustomDesc();

        $data = [
            'merchantConfigurationId' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'serviceTypeId' => 1,
            'localInvoiceId' => $orderId,
            'amountInRials' => $price,
            'localDate' => $localDate,
            'additionalData' => $additionalData,
            'callbackURL' => isset($this->callbackUrl) ? $this->callbackUrl . "/?factor=" . $orderId : Enum::CALL_BACK_URL_ASANPARDAKHT . "/?factor=" . $orderId,
            'paymentId' => '0',
            'settlementPortions' => [
                [
                    'iban' => $this->config->get('gateway.asanpardakht.iban'),
                    'amountInRials' => $this->amount
                ]
            ],
        ];

        $objectRequest = json_encode($data, true);

        try {

            $response = $this->clientsPost($this->serverUrl . "Token", 'POST', $objectRequest, "yes");
            if (isset($response['code']) && isset($response['result']) && $response['code'] == 200) {
                $this->refId = $response['result'];
                $this->transactionSetRefId();
                return true;
            }
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }
        $this->transactionFailed();
        $this->newLog($response['code'], AsanpardakhtException::getMessageByCode($response['code']));
        throw new AsanpardakhtException($response);
    }

    /**
     * @param $payGateTranId
     * @return array
     * @throws AsanpardakhtException
     */
    protected function userPayment($payGateTranId)
    {
        $data = [
            'merchantConfigurationId' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'payGateTranId' => $payGateTranId
        ];
        $objectRequest = json_encode($data);
        $result = [
            'code' => 471
        ];
        try {

            $result = $this->clientsPost($this->serverUrl . "Verify", "POST", $objectRequest, "yes");

            if (isset($result) && $result['code'] == 200) {
                return [
                    'status' => 200,
                    'result' => $result['result']
                ];
            }

        } catch (Exception $e) {
            $this->transactionFailed();
            $this->newLog('httpResponse', $e->getMessage());
            throw $e;
        }

        $this->transactionFailed();
        $this->newLog($result['code'], AsanpardakhtException::getMessageByCode($result['code']));
        throw new AsanpardakhtException($result);
    }

    /**
     * @return int|mixed
     */
    private function getTime()
    {
        $this->setPortName(Enum::ASANPARDAKHT);
        $result = $this->clientsPost($this->serverUrl . "Time", "GET");
        if ($result['code'] == 200) {
            return $result['result'];
        } else {
            return false;
        }
    }

    /**
     * @param $value
     * @return array
     * @throws AsanpardakhtException
     */
    public function checkTransaction($value)
    {
        if ($value) {
            try {
                $result = $this->clientsPost($this->serverUrl . "TranResult?MerchantConfigurationId=" . $this->config->get('gateway.asanpardakht.merchantConfigId') . "&LocalInvoiceId=" . $value . "", "GET", [], "yes");
                if (isset($result) && $result['code'] == 200) {
                    return [
                        'status' => 200,
                        'result' => $result['result']
                    ];
                }
            } catch (Exception $e) {
                $this->transactionFailed();
                $this->newLog('httpResponse', $e->getMessage());
                throw $e;
            }
        }
        $this->transactionFailed();
        $this->newLog($result['code'], AsanpardakhtException::getMessageByCode($result['code']));
        throw new AsanpardakhtException($result);
    }

    /**
     * @param $url
     * @param $methods
     * @param array $options
     * @return int|mixed
     */
    private function clientsPost($url, $methods, $options = array(), $headers = [])
    {
        try {
            $this->username = $this->config->get('gateway.asanpardakht.username');
            $this->password = $this->config->get('gateway.asanpardakht.password');
            if(!empty($headers)){
                $headers = [
                    "Content-Type: application/json",
                    "pwd: $this->password",
                    "usr: $this->username"
                ];
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CUSTOMREQUEST => $methods,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => $options,
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            return [
                'code' => $code,
                'result' => trim($response, '"')
            ];

        } catch (\Exception $e) {
            //$err = curl_error($curl);
            $response = $e->getCode();
        }

        return $response;
    }

}
