<?php

namespace Larabookir\Gateway\Payline;

use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Payline extends PortAbstract implements PortInterface
{
	/**
	 * Address of main CURL server
	 *
	 * @var string
	 */
	protected $serverUrl = 'https://pay.ir/pg/send';

	/**
	 * Address of CURL server for verify payment
	 *
	 * @var string
	 */
	protected $serverVerifyUrl = 'https://pay.ir/pg/verify';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://pay.ir/pg/';

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

        $response = json_decode($response);

		if ($response->status === 1) {
			$this->refId = $response->token;
			$this->transactionSetRefId();

			return true;
		}

		$this->transactionFailed();
        $this->newLog($response->errorCode, PaylineSendException::$errors[$response->errorCode]);
		throw new PaylineSendException($response->errorCode);
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
        $this->token = Request::input('token');
        $status = Request::input('status');

        if ($status === "1") {
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
          'token' => $this->token
        );

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->serverVerifyUrl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		curl_close($ch);

        $response = json_decode($response);

		if ($response->status == 1) {
			$this->transactionSucceed();
			$this->newLog($response->status, Enum::TRANSACTION_SUCCEED_TEXT);

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response->errorCode, PaylineReceiveException::$errors[$response->errorCode]);
		throw new PaylineReceiveException($response->errorCode);
	}
}
