<?php

namespace Hosseinizadeh\Gateway\Payline;

use Hosseinizadeh\Gateway\Enum;
use Hosseinizadeh\Gateway\PortAbstract;
use Hosseinizadeh\Gateway\PortInterface;

class Payline extends PortAbstract implements PortInterface
{
	/**
	 * Address of main CURL server
	 *
	 * @var string
	 */
	protected $serverUrl = 'https://pay.ir/payment/send';

	/**
	 * Address of CURL server for verify payment
	 *
	 * @var string
	 */
	protected $serverVerifyUrl = 'https://pay.ir/payment/verify';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://pay.ir/payment/gateway/';

	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = $amount;

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
		return \Redirect::to($this->gateUrl . $this->refId);
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
			$this->callbackUrl = $this->config->get('gateway.payline.callback-url');

		return urlencode($this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]));
	}

	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws PaylineSendException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = array(
			'api' => $this->config->get('gateway.payline.api'),
			'amount' => $this->amount,
			'redirect' => $this->getCallback(),
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->serverUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		if (is_numeric($response) && $response > 0) {
			$this->refId = $response;
			$this->transactionSetRefId();

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response, PaylineSendException::$errors[$response]);
		throw new PaylineSendException($response);
	}

	/**
	 * Check user payment with GET data
	 *
	 * @return bool
	 *
	 * @throws PaylineReceiveException
	 */
	protected function userPayment()
	{
		$this->refIf = Request('id_get');
		$trackingCode = Request('trans_id');

		if (is_numeric($trackingCode) && $trackingCode > 0) {
			$this->trackingCode = $trackingCode;
			return true;
		}

		$this->transactionFailed();
		$this->newLog(-4, PaylineReceiveException::$errors[-4]);
		throw new PaylineReceiveException(-4);
	}

	/**
	 * Verify user payment from zarinpal server
	 *
	 * @return bool
	 *
	 * @throws PaylineReceiveException
	 */
	protected function verifyPayment()
	{
		$fields = array(
			'api' => $this->config->get('gateway.payline.api'),
			'id_get' => $this->refId(),
			'trans_id' => $this->trackingCode()
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

		if ($response == 1) {
			$this->transactionSucceed();
			$this->newLog($response, Enum::TRANSACTION_SUCCEED_TEXT);

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response, PaylineReceiveException::$errors[$response]);
		throw new PaylineReceiveException($response);
	}
}
