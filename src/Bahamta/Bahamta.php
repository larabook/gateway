<?php

namespace Larabookir\Gateway\Bahamta;

use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Bahamta extends PortAbstract implements PortInterface {

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
    protected $serverUrl = 'https://webpay.bahamta.com/api/create_request';

    protected $verifyUrl = 'https://webpay.bahamta.com/api/confirm_payment';

//    public function __construct( $order_id ) {
//        $this->order_id = $order_id;
//    }

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
        $this->amount = $amount * 10;

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
     * @throws BahamtaException
     */
    protected function sendPayRequest() {
        $this->newTransaction();
//        $cards = Auth::user()->cardsActive()->pluck( 'card_number' );

//        foreach ( $cards as $card ) {
//            $cardString = Str::replaceFirst( "[" , "" , $cards );
//            if ( ! $cards->last() ) {
//                $cardString = Str::replaceFirst( "]" , "," , $cardString );
//            }else{
//                $cardString = Str::replaceFirst( "]" , "" , $cardString );
//            }
//        }
        $params = "?api_key=" . $this->config->get( 'gateway.bahamta.api_key' ) . "&amount_irr=" . $this->amount .
                  "&callback_url=" . $this->getCallback() . "&reference=" . $this->getOrderId();
//                  . "&trusted_pan=" . $cardString;

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->serverUrl . $params );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );

        $result = curl_exec( $ch );
        curl_close( $ch );
        $response = json_decode( $result );
        if ( $response->ok == false ) {
            $this->transactionFailed();
            $this->newLog( $response->error , BahamtaException::$errors[ $response->error ] );
            throw new \Exception(BahamtaException::$errors[ $response->error ]);
        }

        $this->refId        = $this->getOrderId();
        $this->link         = $response->result->payment_url;
        $this->trackingCode = $this->getOrderId();

        $this->transactionSetRefId();
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback() {
        if ( ! $this->callbackUrl ) {
            $this->callbackUrl = $this->config->get( 'gateway.bahamta.callback-url' );
        }

        return $this->makeCallback( $this->callbackUrl , [ 'transaction_id' => $this->transactionId() ] );
    }

    public function getOrderId() {
        return $this->order_id;
    }

    public function setOrderId( $order_id ) {
        $this->order_id = $order_id;
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
        $this->verifyPayment();

        return $this;
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws IdpayException
     */
    protected function userPayment() {
//        $payRequestResCode  = Input::get( 'state' );
//        $this->trackingCode = Input::get( 'track_id' );
////        $this->refId        = Input::get( 'id' );
////        $this->order_id     = Input::get( 'order_id' );
//        $this->amount       = Input::get( 'total' );
//        $this->cardNumber   = Input::get( 'pay_pan' );
//
//        if ( $payRequestResCode == 'paid' ) {
//            return true;
//        }

//        $this->transactionFailed();
//        $this->newLog( $payRequestResCode , IdpayException::$errors[ $payRequestResCode ] );
//        throw new IdpayException( $payRequestResCode );
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws BahamtaException
     * @throws SoapFault
     */
    protected function verifyPayment() {

        $params = "?api_key=" . $this->config->get( 'gateway.bahamta.api_key' ) . "&amount_irr=" . $this->amount .
                  "&reference=" . $this->refId();

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->verifyUrl . $params );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );

        $result = curl_exec( $ch );
        curl_close( $ch );
        $response = json_decode( $result );

        if ( $response->ok == false ) {
            $this->transactionFailed();
            $this->newLog( $response->error , BahamtaException::$errors[ $response->error ] );
            throw new \Exception(BahamtaException::$errors[ $response->error ]);
        }

        $this->trackingCode = $response->result->pay_trace;
        $this->transactionSucceed();
        $this->newLog( 'SUCCEED' , Enum::TRANSACTION_SUCCEED_TEXT );

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
