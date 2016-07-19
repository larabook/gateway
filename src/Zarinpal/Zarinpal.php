<?php

namespace Larabookir\Gateway\Zarinpal;

use DateTime;
use Illuminate\Support\Facades\Input;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

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
				return redirect()->to(str_replace('$Authority', $this->refId, $this->zarinGateUrl));
				break;

			case 'normal':
			default:
				return redirect()->to($this->gateUrl . $this->refId);
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
			'Description' => $this->config->get('gateway.zarinpal.description', ''),
			'Email' => $this->config->get('gateway.zarinpal.email', ''),
			'Mobile' => $this->config->get('gateway.zarinpal.mobile', ''),
		);

		try {
			$soap = new SoapClient($this->serverUrl);
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
		$this->authority = Input::get('Authority');
		$status = Input::get('Status');

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
			$soap = new SoapClient($this->serverUrl);
			$response = $soap->PaymentVerification($fields);

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
}
