<?php

namespace Hosseinizadeh\Gateway\ZarinpalWages;

use DateTime;
use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class ZarinpalWages extends PortAbstract implements PortInterface
{
    /**
     * @var string
     */
    protected $testRequestPostWages = "https://sandbox.zarinpal.com/pg/v4/payment/request.json";
    /**
     * @var string
     */
    protected $testVerifyPostWages = "https://sandbox.zarinpal.com/pg/v4/payment/verify.json";
    /**
     * @var string
     */
    protected $testStartPay = "https://sandbox.zarinpal.com/pg/StartPay/";

    /**
     * @var string
     */
    protected $jsonRequestWages = "https://api.zarinpal.com/pg/v4/payment/request.json";

    /**
     * @var string
     */
    protected $wagesVerify = "https://api.zarinpal.com/pg/v4/payment/verify.json";


    /**
     * Address of germany SOAP server
     *
     * @var string
     */
    protected $germanyServer = 'https://de.zarinpal.com/pg/services/WebGate/wsdl';

    /**
     * Address of iran SOAP server
     *
     * @var string
     */
    protected $iranServer = 'https://www.zarinpal.com/pg/services/WebGate/wsdl';

    /**
     * Address of sandbox SOAP server
     *
     * @var string
     */
    protected $sandboxServer = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';

    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl;

    /**
     * Payment Description
     *
     * @var string
     */
    protected $description;

    /**
     * Payer Email Address
     *
     * @var string
     */
    protected $email;

    /**
     * Payer Mobile Number
     *
     * @var string
     */
    protected $mobileNumber;

    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://www.zarinpal.com/pg/StartPay/';

    /**
     * Address of sandbox gate for redirect
     *
     * @var string
     */
    protected $sandboxGateUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';

    /**
     * Address of zarin gate for redirect
     *
     * @var string
     */
    protected $zarinGateUrl = 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

    public function boot()
    {
        $this->setServer();
    }

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = ($amount / 10);

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
        $this->sendPayRequestWages();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        switch ($this->config->get('gateway.zarinpal.type')) {
            case 'zarin-gate':
                return \Redirect::to(str_replace('$Authority', $this->refId, $this->zarinGateUrl));
                break;

            case 'normal':
            default:
                return \Redirect::to($this->gateUrl . $this->refId);
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);
        $this->userPayment();
        $this->verifyPaymentWages();
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
            $this->callbackUrl = $this->config->get('gateway.zarinpal.callback-url');

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    /**
     * @return bool
     * @throws ZarinpalException
     */
    protected function sendPayRequestWages()
    {
        $this->newTransaction();
        list($array, $errors) = $this->wagesArray();
        if (!isset($array) || $errors == true) {
            return false;
        }

        $data = array(
            "merchant_id" => $this->config->get('gateway.zarinpal.merchant-id'),
            "amount" => $this->amount,
            "callback_url" => $this->getCallback(),
            'description' => $this->description ? $this->description : $this->config->get('gateway.zarinpal.description', ''),
            'metadata' => [
                'mobile' => $this->mobileNumber ? $this->mobileNumber : $this->config->get('gateway.zarinpal.mobile', ''),
                'email' => $this->email ? $this->email : $this->config->get('gateway.zarinpal.email', ''),
            ],
            'wages' => $array,
        );
        $jsonData = json_encode($data);

        try {
            list($result, $err) = $this->curlPostWages($jsonData, $this->jsonRequestWages);
//            list($result, $err) = $this->curlPostWages($jsonData, $this->testRequestPostWages);
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('curl', $e->getMessage());
            throw $e;
        }

        if ($err) {
            $this->transactionFailed();
            $this->newLog('curl', $err);
            throw $err;
        } else {
            if (empty($result['errors'])) {
                if ($result['data']['code'] == 100) {
                    $this->refId = $result['data']["authority"];
                    $this->transactionSetRefId();
                    header('Location: ' . $this->gateUrl . $result['data']["authority"]);
//                    header('Location: ' . $this->testStartPay . $result['data']["authority"]);
                    exit();
                }
            }
            $this->transactionFailed();
            $this->newLog($result['errors']['code'], ZarinpalWagesException::$errorsWages[$result['errors']['code']]);
            throw new ZarinpalWagesException($result['errors']['code']);
        }
    }

    /**
     * @return bool
     * @throws ZarinpalWagesException
     */
    protected function userPayment()
    {
        $this->authority = Request('Authority');
        $status = Request('Status');

        if ($status == 'OK') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog(-22, ZarinpalWagesException::$errors[-22]);
        throw new ZarinpalWagesException(-22);
    }

    /**
     * @return bool
     * @throws ZarinpalException
     */
    protected function verifyPaymentWages()
    {
        $data = array(
            'merchant_id' => $this->config->get('gateway.zarinpal.merchant-id'),
            'authority' => $this->refId,
            'amount' => $this->amount
        );

        $jsonData = json_encode($data);

        try {
            list($result, $err) = $this->curlPostWages($jsonData, $this->wagesVerify);
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('curl', $e->getMessage());
            throw $e;
        }

        if ($err) {
            $this->transactionFailed();
            $this->newLog('curl', $err);
            throw $err;
        } else {
            if (empty($result['errors'])) {
                if ($result['data']['code'] == 100) {
                    $this->trackingCode = $result['data']['ref_id'];
                    $this->transactionSucceed();
                    $this->newLog($result['data']['code'], Enum::TRANSACTION_SUCCEED_TEXT);
                    return true;
                }
            } else {
                $this->transactionFailed();
                $this->newLog($result['errors']['code'], ZarinpalWagesException::$errorsWages[$result['errors']['code']]);
                throw new ZarinpalWagesException($result['errors']['code']);
            }
        }
    }

    /**
     * Set server for soap transfers data
     *
     * @return void
     */
    protected function setServer()
    {
        $server = $this->config->get('gateway.zarinpal.server', 'germany');
        switch ($server) {
            case 'iran':
                $this->serverUrl = $this->jsonRequestWages;
                break;

            case 'test':
                $this->serverUrl = $this->testRequestPostWages;
                $this->gateUrl = $this->testStartPay;
                break;

            case 'germany':
            default:
                $this->serverUrl = $this->germanyServer;
                break;
        }
    }

    /**
     * Set Description
     *
     * @param $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set Payer Email Address
     *
     * @param $email
     * @return void
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Set Payer Mobile Number
     *
     * @param $number
     * @return void
     */
    public function setMobileNumber($number)
    {
        $this->mobileNumber = $number;
    }

    // ================================================= extra function ===================================

    /**
     * @param $jsonData
     * @param $url
     * @return array
     */
    protected function curlPostWages($jsonData, $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        return array($result, $err);
    }

    /**
     * @return array
     */
    protected function wagesArray()
    {
        $errors = false;
        $array = [];
        if (isset($this->wages) && is_array($this->wages) && count($this->wages) <= 2) {
            foreach ($this->wages as $itemWages) {
                $array [] = [
                    'iban' => $itemWages['iban'],
                    'amount' => $itemWages['amount'],
                    'description' => $itemWages['description'],
                ];
            }
        } else {
            $errors = true;
        }
        return array($array, $errors);
    }
}
