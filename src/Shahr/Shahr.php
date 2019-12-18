<?php

namespace Larabookir\Gateway\Shahr;

use Carbon\Carbon;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Shahr extends PortAbstract implements PortInterface
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
    protected $gateUrl = "https://fcp.shaparak.ir/_ipgw_/";

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

        return $this;
    }

    /**
     *
     * Add optional data to the request
     *
     * @param Array $data an array of data
     *
     */
    function setOptionalData (Array $data)
    {
        $this->optional_data = $data;
    }


    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $main_data = [
            'amount'        => $this->amount,
            'merchant'      => $this->config->get('gateway.shahr.merchantId'),
            'resNum'        => $this->transactionId(),
            'callBackUrl'   => $this->getCallback()
        ];

        $data = array_merge($main_data, $this->optional_data);

        return \View::make('gateway::shahr-redirector')->with($data)->with('gateUrl',$this->gateUrl);
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
            $this->callbackUrl = $this->config->get('gateway.shahr.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws ShahrException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $State = request('State');
        $ResNum = request('ResNum');
        $this->refId = request('RefNum');
        $this->trackingCode = request('TraceNo');
        $MID = request('MID');
        $language = request('language');
        $redirectURL = request('redirectURL');
        $merchantData = request('merchantData');
        $transactionAmount = request('transactionAmount');

        $this->getTable()->whereId($this->transactionId)->update([
            'ref_id' => $this->refId,
            'tracking_code' => $this->trackingCode,
            'updated_at' => Carbon::now(),
        ]);

        if ($State == 'OK' and $transactionAmount == $this->amount) {
            $this->transactionSucceed();
            return true;
        }

        $this->transactionFailed();
        $this->newLog(substr($State,0,10), @ShahrException::$errors[$State]);
        throw new ShahrException($State);
    }
}
