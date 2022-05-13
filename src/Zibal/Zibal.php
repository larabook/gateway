<?php

namespace Larabookir\Gateway\Zibal;

use DateTime;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\Zibal\ZibalException;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;
use Illuminate\Support\Facades\Request;

class Zibal extends PortAbstract implements PortInterface {

    protected $order_id;
    protected $link;
    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'https://gateway.zibal.ir/v1/request';

    protected $paymentUrl = 'https://gateway.zibal.ir/start';

    protected $verifyUrl = 'https://gateway.zibal.ir/v1/verify';


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
     * @throws NextpayException
     */
    protected function sendPayRequest() {
        $this->newTransaction();
//        if (auth() - check()) {
//            $userCards = auth()->user()->cards->where('status', 'active')->pluck('last_number');
//        }
        $fields = array (
            'merchant'    => $this->config->get( 'gateway.zibal.merchant' ) ,
            'amount'      => $this->amount ,
            'callbackUrl' => $this->getCallback() ,
        );
//        if ($userCards) {
//            array_push($fields, [
//                'allowedCards' => $userCards,
//            ]);
//        }

        $ch = curl_init();

        curl_setopt( $ch , CURLOPT_URL , $this->serverUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , json_encode( $fields ) );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_HTTPHEADER , array ( 'Content-Type: application/json' ) );


        $response = curl_exec( $ch );
        $response = json_decode( $response );
        curl_close( $ch );

        if ( $response->result == 100 ) {
            $this->refId = $response->trackId;
            $this->link  = "$this->paymentUrl/$this->refId";
            $this->transactionSetRefId();

            return true;
        }

        $this->transactionFailed();
        $this->newLog( $response->result , ZibalException::$errors[ $response->result ] );
        throw new ZibalException( $response->result );
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback() {
        if ( ! $this->callbackUrl ) {
            $this->callbackUrl = $this->config->get( 'gateway.nextpay.callback-url' );
        }

        return $this->makeCallback( $this->callbackUrl , [ 'transaction_id' => $this->transactionId() ] );
    }

    /**
     * {@inheritdoc}
     */
    public function redirect() {
        $link = $this->link;

//        dd($this);
        return redirect( $link );
    }

    /**
     * {@inheritdoc}
     */
    public function verify( $transaction ) {
        parent::verify( $transaction );

        $this->userPayment();
//        $this->validateCardNumber();
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
        $payRequestResCode  = Request::get( 'result' );
        $this->refId        = Request::get( 'trackId' );
        $this->trackingCode = Request::get( 'transaction_id' );
        $status             = Request::get( 'status' );
        $success            = Request::get( 'success' );

        if ( $success == 1 ) {
            return true;
        }

        $this->transactionFailed();
        $this->newLog( $status , ZibalException::$errors[ $status ] );
        throw new ZibalException( $status );
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws NextpayException
     * @throws SoapFault
     */
    protected function verifyPayment() {
        $fields = array (
            'merchant' => $this->config->get( 'gateway.zibal.merchant' ) ,
            'trackId'  => $this->refId ,
        );

        $ch = curl_init();

        curl_setopt( $ch , CURLOPT_URL , $this->verifyUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , json_encode( $fields ) );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_HTTPHEADER , array ( 'Content-Type: application/json' ) );


        $response = curl_exec( $ch );
        $response = json_decode( $response );
//        dd($response->Code);
        curl_close( $ch );

        if ( $response->result  == 100 ) {
            $this->transactionSucceed();
            $this->newLog( 'SUCCESS' , Enum::TRANSACTION_SUCCEED_TEXT );

            return true;
        }

        $this->transactionFailed();
        $this->newLog( $response->result , ZibalException::$errors[ $response->result ] );
        throw new ZibalException( $response->result );

    }

    public function validateCardNumber() {
        if ( auth()->check() ) {
            $userCards = auth()->user()->cards->where( 'status' , 'active' )->pluck( 'last_number' );
            if ( $userCards ) {
                if ( ! $userCards->contains( substr( $this->cardNumber , - 4 ) ) ) {
                    $this->transactionFailed();
                    $this->newLog( 4444 , NextpayException::$errors[ 4444 ] );
                    throw new NextpayException( 4444 );
                }
            } else {
                $this->transactionFailed();
                $this->newLog( 4444 , NextpayException::$errors[ 4444 ] );
                throw new NextpayException( 4444 );
            }
        }
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
