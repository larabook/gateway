<?php

namespace Hosseinizadeh\Gateway\Paypal;

use Hosseinizadeh\Gateway\Mellat\MellatException;
use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\Paypal\PaypalException;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class Paypal extends PortAbstract implements PortInterface
{
    private $_api_context;
    protected $productName;
    protected $shipmentPrice;
    protected $redirectUrl;



    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount;

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

    public function redirect()
    {
        return \Redirect::away($this->redirectUrl);
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback()
    {
        if (!$this->callbackUrl)
            $this->callbackUrl = $this->config->get('gateway.paypal.settings.call_back_url');

        return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
    }

    public function setApiContext()
    {
        $paypal_conf = $this->config->get('gateway.paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']));
        $this->_api_context->setConfig($paypal_conf['settings']);
    }

    public function setShipmentPrice($shipmentPrice)
    {
        $this->shipmentPrice = $shipmentPrice;

        return $this;
    }

    public function ready()
    {
        $this->sendPayRequest();

        return $this;
    }

    public function setRedirectUrl($url)
    {
        $this->redirectUrl = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->setApiContext();
        $this->userPayment();
        if ($this->verifyPayment()) {
            $this->transactionSucceed();
            $this->newLog(200, Enum::TRANSACTION_SUCCEED_TEXT);
        }

        return $this;
    }

    public function setProductName($name){
        $this->productName = $name;

        return $this;
    }

    public function sendPayRequest()
    {
        $this->setApiContext();
        $this->newTransaction();
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName($this->getProductName())// item name
        ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($this->amount); // unit price
        $item_2 = new Item();
        $item_2->setName('Shipment')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($this->getShipmentPrice());
        // add item to list
        $item_list = new ItemList();
        $item_list->setItems([$item_1, $item_2]);
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($this->getShipmentPrice() + $this->amount);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Your transaction description');
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl($this->getCallback())
            ->setCancelUrl($this->getCallback());
        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions([$transaction]);
        try {
            $payment->create($this->_api_context);
        } catch (PayPalConnectionException $ex) {
            if (\Config::get('app.debug')) {
                echo "Exception: " . $ex->getMessage() . PHP_EOL;
                $err_data = json_decode($ex->getData(), true);
                exit;
            } else {
                die('Some error occur, sorry for inconvenient');
            }
        }
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }
        // add payment ID to session
        \Session::put('paypal_payment_id', $payment->getId());

        if (isset($redirect_url)) {
            $this->setRedirectUrl($redirect_url);
        } else {
            $this->setRedirectUrl(\URL::route('original.route'));
        }
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws MellatException
     */
    protected function userPayment()
    {
        $this->refId = Request('PayerID');
        $this->transactionSetRefId();
        $this->trackingCode = Request('token');
    }

    /**
     * Verify user payment from paypal server
     *
     * @return bool
     */
    protected function verifyPayment()
    {
        try {

            /** Get the payment ID before session clear **/
            $payment_id = \Session::get('paypal_payment_id');
            /** clear the session payment ID **/
            \Session::forget('paypal_payment_id');
            if (!$this->refId() || !$this->trackingCode()) {
                return false;
            }
            $payment = Payment::get($payment_id, $this->_api_context);
            /** PaymentExecution object includes information necessary **/
            /** to execute a PayPal account payment. **/
            /** The payer_id is added to the request query parameters **/
            /** when the user is redirected from paypal back to your site **/
            $execution = new PaymentExecution();
            $execution->setPayerId($this->refId);
            /**Execute the payment **/
            $result = $payment->execute($execution, $this->_api_context);
            /** dd($result);exit; /** DEBUG RESULT, remove it later **/
            if ($result->getState() == 'approved') {

                /** it's all right **/
                /** Here Write your database logic like that insert record or value in database if you want **/

//            \Session::put('success','Payment success');
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->transactionFailed();
            $this->newLog('PaypalException', $e->getMessage());
            throw $e;
        }
    }

    public function getProductName(){
        if(!$this->productName){
            return $this->config->get('gateway.paypal.default_product_name');
        }

        return $this->productName;
    }

    public function getShipmentPrice(){
        if(!$this->shipmentPrice){
            return $this->config->get('gateway.paypal.default_shipment_price');
        }

        return $this->shipmentPrice;
    }


}
