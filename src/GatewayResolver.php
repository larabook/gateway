<?php

namespace Larabookir\Gateway;

use Larabookir\Gateway\Irankish\Irankish;
use Larabookir\Gateway\Parsian\Parsian;
use Larabookir\Gateway\Idpay\Idpay;
use Larabookir\Gateway\Paypal\Paypal;
use Larabookir\Gateway\Sadad\Sadad;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Pasargad\Pasargad;
use Larabookir\Gateway\Saman\Saman;
use Larabookir\Gateway\Asanpardakht\Asanpardakht;
use Larabookir\Gateway\Zarinpal\Zarinpal;
use Larabookir\Gateway\Payir\Payir;
use Larabookir\Gateway\Exceptions\RetryException;
use Larabookir\Gateway\Exceptions\PortNotFoundException;
use Larabookir\Gateway\Exceptions\InvalidRequestException;
use Larabookir\Gateway\Exceptions\NotFoundTransactionException;
use Illuminate\Support\Facades\DB;

class GatewayResolver
{
    protected $request;

    /**
     * @var Config
     */
    public $config;

    /**
     * Keep current port driver
     *
     * @var Mellat|Saman|Sadad|Zarinpal|Payir|Idpay|Parsian
     */
    protected $port;

    /**
     * Gateway constructor.
     * @param null $config
     * @param null $port
     */
    public function __construct($config = null, $port = null)
    {
        $this->config = app('config');
        $this->request = app('request');

        if ($this->config->has('gateway.timezone')) {
            date_default_timezone_set($this->config->get('gateway.timezone'));
        }

        if (!is_null($port)) {
            $this->make($port);
        }
    }

    /**
     * Get supported ports
     *
     * @return array
     */
    public function getSupportedPorts()
    {
        return (array) Enum::getIPGs();
    }

    /**
     * Call methods of current driver
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        // calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
        if (in_array(strtoupper($name), $this->getSupportedPorts())) {
            return $this->make($name);
        }

        return call_user_func_array([$this->port, $name], $arguments);
    }

    /**
     * Gets query builder from you transactions table
     * @return mixed
     */
    public function getTable()
    {
        return DB::table($this->config->get('gateway.table'));
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
    public function verify()
    {

        // if( $this->request->has('status') && $this->request->has('track_id') &&  $this->request->status == 10 ){ //FIXME: from 10 to 100
        // 	$id = $this->request->get('id');
        // }else{

            
        // }

        if (!$this->request->has('transaction_id') && !$this->request->has('iN')) {
            throw new InvalidRequestException;
        }
        if ($this->request->has('transaction_id')) {
            $id = \decrypt($this->request->get('transaction_id'));
        } else {
            $id = $this->request->get('iN');
        }

        $transaction = $this->getTable()->whereId($id)->first();

        if (!$transaction) {
            throw new NotFoundTransactionException;
        }

        if (in_array($transaction->status, [Enum::TRANSACTION_SUCCEED, Enum::TRANSACTION_FAILED])) {
            throw new RetryException;
        }

        $this->make($transaction->port);

        return $this->port->verify($transaction);
    }


    /**
     * Create new object from port class
     *
     * @param int $port
     * @throws PortNotFoundException
     */
    public function make($port)
    {
        if ($port instanceof Mellat) {
            $name = Enum::MELLAT;
        } elseif ($port instanceof Parsian) {
            $name = Enum::PARSIAN;
        } elseif ($port instanceof Idpay) {
            $name = Enum::IDPAY;
        } elseif ($port instanceof Saman) {
            $name = Enum::SAMAN;
        } elseif ($port instanceof Zarinpal) {
            $name = Enum::ZARINPAL;
        } elseif ($port instanceof Sadad) {
            $name = Enum::SADAD;
        } elseif ($port instanceof Asanpardakht) {
            $name = Enum::ASANPARDAKHT;
        } elseif ($port instanceof Paypal) {
            $name = Enum::PAYPAL;
        } elseif ($port instanceof Payir) {
            $name = Enum::PAYIR;
        } elseif ($port instanceof Pasargad) {
            $name = Enum::PASARGAD;
        } elseif ($port instanceof Irankish) {
            $name = Enum::IRANKISH;
        } elseif (in_array(strtoupper($port), $this->getSupportedPorts())) {
            $port = ucfirst(strtolower($port));
            $name = strtoupper($port);
            $class = __NAMESPACE__ . '\\' . $port . '\\' . $port;
            $port = new $class;
        } else {
            throw new PortNotFoundException;
        }

        $this->port = $port;
        $this->port->setConfig($this->config); // injects config
        $this->port->setPortName($name); // injects config
        $this->port->boot();

        return $this;
    }
}
