<?php

namespace Larautility\Gateway;

use Larautility\Gateway\Parsian\Parsian;
use Larautility\Gateway\Paypal\Paypal;
use Larautility\Gateway\Sadad\Sadad;
use Larautility\Gateway\Mellat\Mellat;
use Larautility\Gateway\Pasargad\Pasargad;
use Larautility\Gateway\Saman\Saman;
use Larautility\Gateway\Asanpardakht\Asanpardakht;
use Larautility\Gateway\Zarinpal\Zarinpal;
use Larautility\Gateway\Payir\Payir;
use Larautility\Gateway\Exceptions\RetryException;
use Larautility\Gateway\Exceptions\PortNotFoundException;
use Larautility\Gateway\Exceptions\InvalidRequestException;
use Larautility\Gateway\Exceptions\NotFoundTransactionException;
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
	 * @var Mellat|Saman|Sadad|Zarinpal|Payir|Parsian
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

		if ($this->config->has('gateway.timezone'))
			date_default_timezone_set($this->config->get('gateway.timezone'));

		if (!is_null($port)) $this->make($port);
	}

	/**
	 * Get supported ports
	 *
	 * @return array
	 */
	public function getSupportedPorts()
	{
		return [
            Enum::MELLAT,
            Enum::SADAD,
            Enum::ZARINPAL,
            Enum::PARSIAN,
            Enum::PASARGAD,
            Enum::SAMAN,
            Enum::PAYPAL,
            Enum::ASANPARDAKHT,
            Enum::PAYIR
        ];
	}

	/**
	 * Call methods of current driver
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{

		// calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
		if(in_array(strtoupper($name),$this->getSupportedPorts())){
			return $this->make($name);
		}

		return call_user_func_array([$this->port, $name], $arguments);
	}

	/**
	 * Gets query builder from you transactions table
	 * @return mixed
	 */
	function getTable()
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
		if (!$this->request->has('transaction_id') && !$this->request->has('iN'))
			throw new InvalidRequestException;
		if ($this->request->has('transaction_id')) {
			$id = $this->request->get('transaction_id');
		}else {
			$id = $this->request->get('iN');
		}

		$transaction = $this->getTable()->whereId($id)->first();

		if (!$transaction)
			throw new NotFoundTransactionException;

		if (in_array($transaction->status, [Enum::TRANSACTION_SUCCEED, Enum::TRANSACTION_FAILED]))
			throw new RetryException;

		$this->make($transaction->port);

		return $this->port->verify($transaction);
	}


	/**
	 * Create new object from port class
	 *
	 * @param int $port
	 * @throws PortNotFoundException
	 */
	function make($port)
	{
		if ($port InstanceOf Mellat) {
			$name = Enum::MELLAT;
		} elseif ($port InstanceOf Parsian) {
			$name = Enum::PARSIAN;
		} elseif ($port InstanceOf Saman) {
			$name = Enum::SAMAN;
		} elseif ($port InstanceOf Zarinpal) {
			$name = Enum::ZARINPAL;
		} elseif ($port InstanceOf Sadad) {
			$name = Enum::SADAD;
		} elseif ($port InstanceOf Asanpardakht) {
			$name = Enum::ASANPARDAKHT;
		} elseif ($port InstanceOf Paypal) {
			$name = Enum::PAYPAL;
		} elseif ($port InstanceOf Payir) {
			$name = Enum::PAYIR;
		}  elseif(in_array(strtoupper($port),$this->getSupportedPorts())){
			$port=ucfirst(strtolower($port));
			$name=strtoupper($port);
			$class=__NAMESPACE__.'\\'.$port.'\\'.$port;
			$port=new $class;
		} else
			throw new PortNotFoundException;

		$this->port = $port;
		$this->port->setConfig($this->config); // injects config
		$this->port->setPortName($name); // injects config
		$this->port->boot();

		return $this;
	}
}
