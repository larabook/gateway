<?php

namespace Masihjazayeri\Gateway\Saderat;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Request;
use SoapClient;
use Masihjazayeri\Gateway\PortAbstract;
use Masihjazayeri\Gateway\PortInterface;

class Saderat extends PortAbstract implements PortInterface
{
    /**
     *
     * @var Array $optional_data An array of optional data
     *  that will be sent with the payment request
     *
     */
    protected $optional_data = [];

    /**
     * Address of main SOAP server
     *
     * @var string
     */


    protected $advice_url = 'https://mabna.shaparak.ir:8081/V1/PeymentApi/Advice';

    protected $getTokenUrl = "https://mabna.shaparak.ir:8081/V1/PeymentApi/GetToken";

    protected $redirectUrl = "https://mabna.shaparak.ir:8080/pay";


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
        $this->newTransaction();
        $this->getRedirectToken();
        return $this;
    }

    /**
     *
     * Add optional data to the request
     *
     * @param Array $data an array of data
     *
     */
    function setOptionalData(Array $data)
    {
        $this->optional_data = $data;
    }

    function makeHttpChargeRequest( $_Data, $_Address)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $_Address);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $_Data);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;

    }
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    public function getRedirectToken()
    {
        $dataQuery ='Amount='.$this->test_input($this->amount).'&callbackURL='.$this->test_input($this->getCallback()).'&InvoiceID='.$this->test_input($this->transactionId()).'&TerminalID='.$this->test_input($this->config->get('gateway.saderat.terminalID'));
        try {
            $response = $this->makeHttpChargeRequest($dataQuery, $this->getTokenUrl);

        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('http', $e->getMessage());
            throw $e;
        }

        $response = json_decode($response);
        $Status = $response->Status;
        $AccessToken = $response->Accesstoken;

        if (!empty($AccessToken) && $Status == 0) {
            $this->refId = $AccessToken;
            $this->transactionSetRefId();
            return;
        }
        $this->transactionFailed();
        $this->newLog($Status, $AccessToken);
        throw new SaderatException($Status);
    }


    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $main_data = [
            'token' => $this->refId,
            'terminalID' => $this->config->get('gateway.saderat.terminalID'),
        ];

        $data = array_merge($main_data, $this->optional_data);

        return \View::make('gateway::saderat-redirector')->with($data)->with('gateUrl', $this->redirectUrl);
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
            $this->callbackUrl = $this->config->get('gateway.saderat.callback-url');


        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }


    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws SaderatException
     */
    protected function userPayment()
    {
        $this->trackingCode = Request::input('tracenumber');
        $this->cardNumber = Request::input('cardnumber'); 
        $payRequestRes = Request::input('respmsg');
        $payRequestResCode = Request::input('respcode');

        $this->refId = Request::input('rrn');
        $this->getTable()->whereId($this->transactionId)->update([
            'ref_id' => $this->refId,
            'tracking_code' => $this->trackingCode,
            'card_number' => $this->cardNumber,
            'updated_at' => Carbon::now(),
        ]);

        if ($$payRequestResCode == '0') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($payRequestResCode, @SaderatException::$errors[$payRequestRes]);
        throw new SaderatException($payRequestRes);
    }


    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws SaderatException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
    
        $dataQuery ='digitalreceipt='.$this->test_input(Request::input('digitalreceipt')).'&Tid='.$this->config->get('gateway.saderat.terminalID');

        $response =json_decode( makeHttpChargeRequest('POST',$dataQuery,$AdviceAddress));

        $Status =$response->Status;
        $ReturnId=$response->ReturnId;
        $Message=$response->Message;

        if ( $respcode == 0 && $Status== "Ok" && $ReturnId==$this->amount) {
            $this->transactionSucceed();
            return true;
        }
        //
        $this->transactionSetRefId();
        $this->transactionFailed();
        $this->newLog($response, SaderatException::$errors[$respcode]);
        throw new SaderatException($response);


    }


}
