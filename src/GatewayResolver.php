<?php

namespace Larabookir\Gateway;

use Larabookir\Gateway\Bahamta\Bahamta;
use Larabookir\Gateway\Idpay\Idpay;
use Larabookir\Gateway\Irankish\Irankish;
use Larabookir\Gateway\Mrpay\Mrpay;
use Larabookir\Gateway\Nextpay\Nextpay;
use Larabookir\Gateway\Parsian\Parsian;
use Larabookir\Gateway\Paypal\Paypal;
use Larabookir\Gateway\Paystar\Paystar;
use Larabookir\Gateway\Poolam\Poolam;
use Larabookir\Gateway\Sadad\Sadad;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Pasargad\Pasargad;
use Larabookir\Gateway\Saman\Saman;
use Larabookir\Gateway\Asanpardakht\Asanpardakht;
use Larabookir\Gateway\Yekpay\Yekpay;
use Larabookir\Gateway\Zarinpal\Zarinpal;
use Larabookir\Gateway\Payir\Payir;
use Larabookir\Gateway\Exceptions\RetryException;
use Larabookir\Gateway\Exceptions\PortNotFoundException;
use Larabookir\Gateway\Exceptions\InvalidRequestException;
use Larabookir\Gateway\Exceptions\NotFoundTransactionException;
use Illuminate\Support\Facades\DB;
use Larabookir\Gateway\Zibal\Zibal;

class GatewayResolver {

    /**
     * @var Config
     */
    public $config;
    protected $request;
    /**
     * Keep current port driver
     *
     * @var Mellat|Saman|Sadad|Zarinpal|Payir|Parsian
     */
    protected $port;

    /**
     * Gateway constructor.
     *
     * @param null $config
     * @param null $port
     */
    public function __construct( $config = null , $port = null ) {
        $this->config  = app( 'config' );
        $this->request = app( 'request' );

        if ( $this->config->has( 'gateway.timezone' ) ) {
            date_default_timezone_set( $this->config->get( 'gateway.timezone' ) );
        }

        if ( ! is_null( $port ) ) {
            $this->make( $port );
        }
    }

    /**
     * Create new object from port class
     *
     * @param int $port
     *
     * @throws PortNotFoundException
     */
    function make( $port ) {
        if ( $port instanceof Mellat ) {
            $name = Enum::MELLAT;
        } elseif ( $port instanceof Parsian ) {
            $name = Enum::PARSIAN;
        } elseif ( $port instanceof Saman ) {
            $name = Enum::SAMAN;
        } elseif ( $port instanceof Zarinpal ) {
            $name = Enum::ZARINPAL;
        } elseif ( $port instanceof Sadad ) {
            $name = Enum::SADAD;
        } elseif ( $port instanceof Asanpardakht ) {
            $name = Enum::ASANPARDAKHT;
        } elseif ( $port instanceof Paypal ) {
            $name = Enum::PAYPAL;
        } elseif ( $port instanceof Payir ) {
            $name = Enum::PAYIR;
        } elseif ( $port instanceof Pasargad ) {
            $name = Enum::PASARGAD;
        } elseif ( $port instanceof Irankish ) {
            $name = Enum::IRANKISH;
        } elseif ( $port instanceof Idpay ) {
            $name = Enum::IDPAY;
        } elseif ( $port instanceof Payping ) {
            $name = Enum::PAYPING;
        } elseif ( $port instanceof Nextpay ) {
            $name = Enum::NEXTPAY;
        } elseif ( $port instanceof Bahamta ) {
            $name = Enum::BAHAMTA;
        } elseif ( $port instanceof Yekpay ) {
            $name = Enum::YEKPAY;
        } elseif ( $port instanceof Mrpay ) {
            $name = Enum::MRPAY;
        } elseif ( $port instanceof Zibal ) {
            $name = Enum::ZIBAL;
        } elseif ( $port instanceof Poolam ) {
            $name = Enum::POOLAM;
        } elseif ( $port instanceof Paystar ) {
            $name = Enum::PAYSTAR;
        } elseif ( in_array( strtoupper( $port ) , $this->getSupportedPorts() ) ) {
            $port  = ucfirst( strtolower( $port ) );
            $name  = strtoupper( $port );
            $class = __NAMESPACE__ . '\\' . $port . '\\' . $port;
            $port  = new $class;
        } else {
            throw new PortNotFoundException;
        }

        $this->port = $port;
        $this->port->setConfig( $this->config ); // injects config
        $this->port->setPortName( $name ); // injects config
        $this->port->boot();

        return $this;
    }

    /**
     * Get supported ports
     *
     * @return array
     */
    public function getSupportedPorts() {
        return (array) Enum::getIPGs();
    }

    /**
     * Call methods of current driver
     *
     * @return mixed
     */
    public function __call( $name , $arguments ) {

        // calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
        if ( in_array( strtoupper( $name ) , $this->getSupportedPorts() ) ) {
            return $this->make( $name );
        }

        return call_user_func_array( [ $this->port , $name ] , $arguments );
    }

    /**
     * Callback
     *
     * @return $this->port
     *
     * @throws InvalidRequestException
     * @throws NotFoundTransactionException
     * @throws PortNotFoundException
     * @throws RetryException
     */
    public function verify() {
        if ( ! $this->request->has( 'transaction_id' ) && ! $this->request->has( 'iN' ) ) {
            throw new InvalidRequestException;
        }
        if ( $this->request->has( 'transaction_id' ) ) {
            $id = $this->request->get( 'transaction_id' );
        } else {
            $id = $this->request->get( 'iN' );
        }

        $transaction = $this->getTable()->whereId( $id )->first();

        if ( ! $transaction ) {
            throw new NotFoundTransactionException;
        }

        if ( in_array( $transaction->status , [ Enum::TRANSACTION_SUCCEED , Enum::TRANSACTION_FAILED ] ) ) {
            throw new RetryException;
        }

        $this->make( $transaction->port );

        return $this->port->verify( $transaction );
    }

    /**
     * Gets query builder from you transactions table
     * @return mixed
     */
    function getTable() {
        return DB::table( $this->config->get( 'gateway.table' ) );
    }
}
