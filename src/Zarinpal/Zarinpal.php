<?php

namespace Hosseinizadeh\Gateway\Zarinpal;

use DateTime;
use Hosseinizadeh\Gateway\Enum;
use SoapClient;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Zarinpal extends PortAbstract implements PortInterface
{
	/**
	 * Address of germany SOAP server
	 *
	 * @var string
	 */
	protected $germanyServer = 'https://de.zarinpal.com/pg/services/WebGate/wsdl';

	/**
	 * Address of iran SOAP server
	 *
	 * @var string
	 */
	protected $iranServer = 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';

    /**
	 * Address of sandbox SOAP server
	 *
	 * @var string
	 */
	protected $sandboxServer = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';

	/**
	 * Address of main SOAP server
	 *
	 * @var string
	 */
	protected $serverUrl;

	/**
	 * Payment Description
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Payer Email Address
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Payer Mobile Number
	 *
	 * @var string
	 */
	protected $mobileNumber;

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://www.zarinpal.com/pg/StartPay/';

    /**
	 * Address of sandbox gate for redirect
	 *
	 * @var string
	 */
	protected $sandboxGateUrl = 'https://sandbox.zarinpal.com/pg/StartPay/';

	/**
	 * Address of zarin gate for redirect
	 *
	 * @var string
	 */
	protected $zarinGateUrl = 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

	public function boot()
	{
		$this->setServer();
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = ($amount / 10);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready()
	{
		$this->sendPayRequest();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect()
	{
		switch ($this->config->get('gateway.zarinpal.type')) {
			case 'zarin-gate':
				return \Redirect::to(str_replace('$Authority', $this->refId, $this->zarinGateUrl));
				break;

			case 'normal':
			default:
				return \Redirect::to($this->gateUrl . $this->refId);
				break;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->userPayment();
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
			$this->callbackUrl = $this->config->get('gateway.zarinpal.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws ZarinpalException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = array(
			'MerchantID' => $this->config->get('gateway.zarinpal.merchant-id'),
			'Amount' => $this->amount,
			'CallbackURL' => $this->getCallback(),
			'Description' => $this->description ? $this->description : $this->config->get('gateway.zarinpal.description', ''),
			'Email' => $this->email ? $this->email :$this->config->get('gateway.zarinpal.email', ''),
			'Mobile' => $this->mobileNumber ? $this->mobileNumber : $this->config->get('gateway.zarinpal.mobile', ''),
		);

		try {
			$soap = new SoapClient($this->serverUrl, ['encoding' => 'UTF-8']);
			$response = $soap->PaymentRequest($fields);

		} catch (\SoapFault $e) {
			$this->transactionFailed();
			$this->newLog('SoapFault', $e->getMessage());
			throw $e;
		}

		if ($response->Status != 100) {
			$this->transactionFailed();
			$this->newLog($response->Status, ZarinpalException::$errors[$response->Status]);
			throw new ZarinpalException($response->Status);
		}

		$this->refId = $response->Authority;
		$this->transactionSetRefId();
	}

	/**
	 * Check user payment with GET data
	 *
	 * @return bool
	 *
	 * @throws ZarinpalException
	 */
	protected function userPayment()
	{
		$this->authority = Request('Authority');
		$status = Request('Status');

		if ($status == 'OK') {
			return true;
		}

		$this->transactionFailed();
		$this->newLog(-22, ZarinpalException::$errors[-22]);
		throw new ZarinpalException(-22);
	}

	/**
	 * Verify user payment from zarinpal server
	 *
	 * @return bool
	 *
	 * @throws ZarinpalException
	 */
	protected function verifyPayment()
	{

		$fields = array(
			'MerchantID' => $this->config->get('gateway.zarinpal.merchant-id'),
			'Authority' => $this->refId,
			'Amount' => $this->amount,
		);

		try {
			$soap = new SoapClient($this->serverUrl, ['encoding' => 'UTF-8']);
			$response = $soap->PaymentVerification($fields);

		} catch (\SoapFault $e) {
			$this->transactionFailed();
			$this->newLog('SoapFault', $e->getMessage());
			throw $e;
		}

		if ($response->Status != 100 && $response->Status != 101) {
			$this->transactionFailed();
			$this->newLog($response->Status, ZarinpalException::$errors[$response->Status]);
			throw new ZarinpalException($response->Status);
		}

		$this->trackingCode = $response->RefID;
		$this->transactionSucceed();
		$this->newLog($response->Status, Enum::TRANSACTION_SUCCEED_TEXT);
		return true;
	}

	/**
	 * Set server for soap transfers data
	 *
	 * @return void
	 */
	protected function setServer()
	{
		$server = $this->config->get('gateway.zarinpal.server', 'germany');
		switch ($server) {
			case 'iran':
				$this->serverUrl = $this->iranServer;
				break;

			case 'test':
				$this->serverUrl = $this->sandboxServer;
				$this->gateUrl = $this->sandboxGateUrl;
				break;

			case 'germany':
			default:
				$this->serverUrl = $this->germanyServer;
				break;
		}
	}


	/**
	 * Set Description
	 *
	 * @param $description
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Set Payer Email Address
	 *
	 * @param $email
	 * @return void
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Set Payer Mobile Number
	 *
	 * @param $number
	 * @return void
	 */
	public function setMobileNumber($number)
	{
		$this->mobileNumber = $number;
	}
}
