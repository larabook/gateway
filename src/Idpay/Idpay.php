<?php

namespace Larabookir\Gateway\Idpay;

use DateTime;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Idpay extends PortAbstract implements PortInterface {

    protected $order_id;
    protected $link;
    protected $name;
    protected $phone;
    protected $email;
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://api.idpay.ir/v1.1/payment';

    protected $verifyUrl = 'https://api.idpay.ir/v1.1/payment/verify';

    protected $settelUrl = 'https://api.idpay.ir/v1.1/payment/inquiry';

//    public function __construct( $order_id ) {
//        $this->order_id = $order_id;
//    }

    public function getOrderId() {
        return $this->order_id;
    }

    public function setOrderId( $order_id ) {
        $this->order_id = $order_id;
    }

    public function getLink() {
        return $this->link;
    }

    public function setLink( $link ) {
        $this->link = $link;
    }

    /**
     * {@inheritdoc}
     */
    public function set( $amount ) {
        $this->amount = $amount *10;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready() {
        $this->sendPayRequest();

        return $this;
    }

    /**
     * Send pay request to server
     *
     * @return void
     *
     * @throws MellatException
     */
    protected function sendPayRequest() {
        $this->newTransaction();

        $params = array (
            'order_id' => $this->order_id ,
            'amount'   => $this->amount ,
            'callback' => $this->getCallback() ,
        );

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->serverUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , json_encode( $params ) );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_HTTPHEADER , array (
            'Content-Type: application/json' ,
            'X-API-KEY: ' . $this->config->get( 'gateway.idpay.api-key' ) . '' ,
            'X - SANDBOX: 0'
        ) );

        $result = curl_exec( $ch );
        curl_close( $ch );

        $response = json_decode( $result );
        if ( isset( $response->error_code ) ) {
            $this->transactionFailed();
            $this->newLog( $response->error_code , IdpayException::$errors[ $response->error_code ] );
            throw new IdpayException( $response->error_code );
        }

        $this->refId        = $response->id;
        $this->link         = $response->link;
        $this->trackingCode = $response->id;

        $this->transactionSetRefId();
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback() {
        if ( ! $this->callbackUrl ) {
            $this->callbackUrl = $this->config->get( 'gateway.idpay.callback-url' );
        }

        return $this->makeCallback( $this->callbackUrl , [ 'transaction_id' => $this->transactionId() ] );
    }

    /**
     * {@inheritdoc}
     */
    public function redirect() {
        $link = $this->link;

        return redirect( $link );
    }

    /**
     * {@inheritdoc}
     */
    public function verify( $transaction ) {
        parent::verify( $transaction );

        $this->userPayment();
        $this->validateCardNumber();
        $this->verifyPayment();

        return $this;
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws MellatException
     */
    protected function userPayment() {
        $payRequestResCode  = Request::input( 'status' );
        $this->trackingCode = Request::input( 'track_id' );
        $this->refId        = Request::input( 'id' );
        $this->order_id     = Request::input( 'order_id' );
        $this->amount       = Request::input( 'amount' );
        $this->cardNumber   = Request::input( 'card_no' );

        if ( $payRequestResCode == 100 ) {
            return true;
        }

//        $this->transactionFailed();
//        $this->newLog( $payRequestResCode , IdpayException::$errors[ $payRequestResCode ] );
//        throw new IdpayException( $payRequestResCode );
    }

    public function validateCardNumber() {
        if ( auth()->check() ) {
            $userCards = auth()->user()->cards->where( 'status' , 1 )->pluck( 'last_number' );
            if ( $userCards ) {
                if ( ! $userCards->contains( substr( $this->cardNumber , - 4 ) ) ) {
                    $this->transactionFailed();
                    $this->newLog( 4444 , IdpayException::$errors[ 4444 ] );
                    throw new IdpayException( 4444 );
                }
            } else {
                $this->transactionFailed();
                $this->newLog( 4444 , IdpayException::$errors[ 4444 ] );
                throw new IdpayException( 4444 );
            }
        }
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws MellatException
     * @throws SoapFault
     */
    protected function verifyPayment() {

        $params = array (
            'id'       => $this->refId ,
            'order_id' => $this->order_id ,
        );

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->verifyUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , json_encode( $params ) );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_HTTPHEADER , array (
            'Content-Type: application/json' ,
            'X-API-KEY: ' . $this->config->get( 'gateway.idpay.api-key' ) . '' ,
            'X-SANDBOX: 0' ,
        ) );
        $result = curl_exec( $ch );
        curl_close( $ch );
        $response = json_decode( $result );
        if ( isset( $response->error_code ) ) {
            $this->transactionFailed();
            $this->newLog( $response->error_code , IdpayException::$errors[ $response->error_code ] );
            throw new IdpayException( $response->error_code );
        }
        $this->trackingCode = $response->track_id;
        $this->transactionSucceed();
        $this->newLog( $response->status , Enum::TRANSACTION_SUCCEED_TEXT );

        return true;
    }

    /**
     * Sets callback url
     *
     * @param $url
     */
    function setCallback( $url ) {
        $this->callbackUrl = $url;

        return $this;
    }

    /**
     * Send settle request
     *
     * @return bool
     *
     * @throws MellatException
     * @throws SoapFault
     */
    protected function settleRequest() {

        $params = array (
            'id'       => $this->refId ,
            'order_id' => $this->order_id ,
        );

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->settelUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , json_encode( $params ) );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_HTTPHEADER , array (
            'Content-Type: application/json' ,
            'X-API-KEY: ' . $this->config->get( 'gateway.idpay.api-key' ) . '' ,
            'X-SANDBOX: 1' ,
        ) );

        $result   = curl_exec( $ch );
        $httpcode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        curl_close( $ch );

        var_dump( $httpcode );
        var_dump( $result );
    }
}
