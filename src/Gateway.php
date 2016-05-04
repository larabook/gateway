<?php

namespace Larabookir\Gateway;

use Larabookir\Gateway\Parsian\Parsian;
use Larabookir\Gateway\Sadad\Sadad;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Payline\Payline;
use Larabookir\Gateway\Zarinpal\Zarinpal;
use Larabookir\Gateway\JahanPay\JahanPay;
use Larabookir\Gateway\Exceptions\RetryException;
use Larabookir\Gateway\Exceptions\PortNotFoundException;
use Larabookir\Gateway\Exceptions\InvalidRequestException;
use Larabookir\Gateway\Exceptions\NotFoundTransactionException;

class Gateway
{
	const MELLAT = 'MELLAT';

	const SADAD = 'SADAD';

	const ZARINPAL = 'ZARINPAL';

	const PAYLINE = 'PAYLINE';

	const JAHANPAY = 'JAHANPAY';

	const PARSIAN = 'PARSIAN';

	protected $request;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Keep current port driver
	 *
	 * @var Mellat|Sadad|Zarinpal|Payline|JahanPay
	 */
	protected $port;

	/**
	 * Gateway constructor.
	 * @param null $config
	 * @param null $port
	 */
	public function __construct($config = null,$port = null)
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
		return [self::MELLAT, self::SADAD, self::ZARINPAL, self::PAYLINE, self::JAHANPAY];
	}

	/**
	 * Call methods of current driver
	 *
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->port, $name], $arguments);
	}

	/**
	 * Gets query builder from you transactions table
	 * @return mixed
	 */
	function getTable()
	{
		return DB::table($this->config->get('gateway.db_tables.transactions'));
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
		if (!$this->request->has('transaction_id'))
			throw new InvalidRequestException;

		$id = intval($this->request('transaction_id'));

		$transaction = $this->getTable()->whereId($id)->first();

		if (!$transaction)
			throw new NotFoundTransactionException;

		if (in_array($transaction->status, [PortAbstract::TRANSACTION_SUCCEE, PortAbstract::TRANSACTION_FAILED]))
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
		switch ($port) {
			case self::MELLAT:
				$this->port = new Mellat($this->config, self::MELLAT);
				break;

			case self::SADAD:
				$this->port = new Sadad($this->config, self::SADAD);
				break;

			case self::ZARINPAL:
				$this->port = new Zarinpal($this->config, self::ZARINPAL);
				break;

			case self::PAYLINE:
				$this->port = new Payline($this->config, self::PAYLINE);
				break;

			case self::JAHANPAY:
				$this->port = new JahanPay($this->config, self::JAHANPAY);
				break;

			case self::PARSIAN:
				$this->port = new Parsian($this->config, self::PARSIAN);
				break;

			default:
				throw new PortNotFoundException;
				break;
		}

		return $this;
	}
}
