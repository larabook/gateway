<?php

namespace Hosseinizadeh\Gateway\Asanpardakht;

use Hosseinizadeh\Gateway\Enum;
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
     * @param array $wages
     * @return $this
     */
    public function setWages(array $wages)
    {
        $this->wages = $wages;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        if (isset($this->wages) && count($this->wages)) {
            $this->sendPayRequestWages();
            return $this;
        }
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
     * @param object $transaction
     * @return $this|PortAbstract|PortInterface
     * @throws AsanpardakhtException
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->transactionId = $transaction->id;
        $resultCheckTransaction = $this->checkTransaction($transaction->id);
        $resultVerify = [
            'status' => 471,
            'code' => 471
        ];

        if (isset($resultCheckTransaction['status']) && $resultCheckTransaction['status'] == 200) {
            $jsonDecode = json_decode($resultCheckTransaction['result']);
            if (isset($jsonDecode->payGateTranID)) {

                $this->rrn = $jsonDecode->rrn;
                $this->cardNumber = $jsonDecode->cardNumber;
                $salesOrderID = $jsonDecode->salesOrderID;
                $this->trackingCode = $jsonDecode->payGateTranID;

                $resultVerify = $this->userPayment($jsonDecode->payGateTranID);

                if ($resultVerify['status'] == 200) {
                    $this->transactionSucceed();
                    $this->newLog($resultVerify['status'], Enum::TRANSACTION_SUCCEED_TEXT);
                } else {
                    $this->transactionFailed();
                    $this->newLog($resultVerify['status'], AsanpardakhtException::getMessageByCodeVerify($resultVerify['status']));
                    throw new AsanpardakhtException($resultVerify);
                }
            } else {
                $this->transactionFailed();
                $this->newLog($resultVerify['status'], AsanpardakhtException::getMessageByCodeVerify($resultVerify['status']));
                throw new AsanpardakhtException($resultVerify);
            }
        } else {
            $this->transactionFailed();
            $this->newLog($resultVerify['status'], AsanpardakhtException::getMessageByCodeVerify($resultVerify['status']));
            throw new AsanpardakhtException($resultVerify);
        }

        return $this;
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
            'callbackURL' => isset($this->callbackUrl) ? $this->callbackUrl . "/?transaction_id=" . $orderId : Enum::CALL_BACK_URL_ASANPARDAKHT . "/?transaction_id=" . $orderId,
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
     * @return bool
     * @throws AsanpardakhtException
     */
    protected function sendPayRequestWages(){

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

        list($array, $errors) = $this->wagesArray();
        if (!isset($array) || $errors == true) {
            return false;
        }

        $data = [
            'merchantConfigurationId' => $this->config->get('gateway.asanpardakht.merchantConfigId'),
            'serviceTypeId' => 1,
            'localInvoiceId' => $orderId,
            'amountInRials' => $price,
            'localDate' => $localDate,
            'additionalData' => $additionalData,
            'callbackURL' => isset($this->callbackUrl) ? $this->callbackUrl . "/?transaction_id=" . $orderId : Enum::CALL_BACK_URL_ASANPARDAKHT . "/?transaction_id=" . $orderId,
            'paymentId' => '0',
            'settlementPortions' => $array
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
            'status' => 471,
            'code' => 471
        ];
        try {

            $result = $this->clientsPost($this->serverUrl . "Verify", "POST", $objectRequest, "yes");

            if (isset($result) && $result['code'] == 200) {
                return [
                    'status' => 200,
                    'code' => 200,
                    'result' => $result['result']
                ];
            }

        } catch (\Exception $e) {
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
     * @return array|bool
     * @throws \Exception
     */
    public function checkTransaction($value)
    {
        if ($value) {
            try {

                $result = $this->clientsPost($this->serverUrl . "TranResult?merchantConfigurationId=" . $this->config->get('gateway.asanpardakht.merchantConfigId') . "&localInvoiceId=" . $value . "", "GET", [], "yes");

                if (isset($result) && $result['code'] == 200) {
                    return [
                        'status' => 200,
                        'result' => $result['result']
                    ];
                }
            } catch (\Exception $e) {
                $this->transactionFailed();
                $this->newLog('httpResponse', $e->getMessage());
                throw $e;
            }
        }
        return true;
    }

    /**
     * @param $url
     * @param $methods
     * @param array $options
     * @param array $headers
     * @return array|bool|int|mixed|string
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

    /**
     * @return array
     */
    protected function wagesArray()
    {
        $errors = false;
        $array = [];
        if (isset($this->wages) && is_array($this->wages)) {
            foreach ($this->wages as $itemWages) {
                $array [] = [
                    'iban' => $itemWages['iban'],
                    'amountInRials' => $itemWages['amount']
                ];
            }
        } else {
            $errors = true;
        }
        return array($array, $errors);
    }
}
