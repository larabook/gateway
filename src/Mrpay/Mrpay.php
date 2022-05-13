<?php

namespace Larabookir\Gateway\Mrpay;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Mrpay extends PortAbstract implements PortInterface {
    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'http://panel.aqayepardakht.ir/api/create/';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'http://panel.aqayepardakht.ir/api/verify/';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'http://panel.aqayepardakht.ir/startpay/';


    protected $factorNumber;

    /**
     * {@inheritdoc}
     */
    public function set( $amount ) {
        $this->amount = $amount;

        return $this;
    }

    /**
     * تعیین شماره فاکتور (اختیاری)
     *
     * @param $factorNumber
     *
     * @return $this
     */
    public function setFactorNumber( $factorNumber ) {
        $this->factorNumber = $factorNumber;

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
     * @throws MrpaySendException
     */
    protected function sendPayRequest() {
        $this->newTransaction();
        if ( Auth::user()->cardsActive->count() < 1 ) {
            flash( 'شماره کارت شما ثبت نشده است' )->error()->important();

            return back();
            exit();
        }
        $cardsActive = Auth::user()->cardsActive[ 0 ]->card_number;
        $fields      = [
            'pin'         => $this->config->get( 'gateway.mrpay.pin' ) ,
            'amount'      => $this->amount ,
            'callback'    => $this->getCallback() ,
            'description' => '' ,
            'card_number' => $cardsActive
        ];

        $ch = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->serverUrl );
        curl_setopt( $ch , CURLOPT_POST , count( $fields ) );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , http_build_query( $fields ) );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        $response = curl_exec( $ch );
        curl_close( $ch );
        if ( ! is_numeric( $response ) ) {
            $this->refId = $response;
            $this->transactionSetRefId();

            return true;
        }
        $this->transactionFailed();
        $this->newLog( $response , MrpaySendException::$errors[ $response ] );
        throw new MrpaySendException( $response );
    }

    /**
     * Gets callback url
     * @return string
     */
    function getCallback() {
        if ( ! $this->callbackUrl ) {
            $this->callbackUrl = $this->config->get( 'gateway.mrpay.callback-url' );
        }

        return $this->makeCallback( $this->callbackUrl , [ 'transaction_id' => $this->transactionId() ] );
    }

    /**
     * {@inheritdoc}
     */
    public function redirect() {
        return redirect()->to( $this->gateUrl . $this->refId );
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
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws MrpayReceiveException
     */
    protected function userPayment() {
//        $amount       = Request::input('amount');
        $transId     = Request::input( 'transid' );
        $this->refId = $transId;
//        $this->amount = $amount;
    }

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws MrpaySendException
     */
    protected function verifyPayment() {
        $fields = [
            'pin'     => $this->config->get( 'gateway.mrpay.pin' ) ,
            'transid' => $this->refId() ,
            'amount'  => $this->amount ,
        ];
        $ch     = curl_init();
        curl_setopt( $ch , CURLOPT_URL , $this->serverVerifyUrl );
        curl_setopt( $ch , CURLOPT_POSTFIELDS , http_build_query( $fields ) );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        $response = curl_exec( $ch );
        curl_close( $ch );

        if ( $response == 1 ) {
            $this->transactionSucceed();
            $this->newLog( 1 , Enum::TRANSACTION_SUCCEED_TEXT );

            return true;
        }

        $this->transactionFailed();
        $this->newLog( $response , MrpaySendException::$errors[ $response ] );
        throw new MrpaySendException( $response );
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
}
