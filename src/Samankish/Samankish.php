<?php

namespace Larabookir\Gateway\Samankish;

use Exception;
use Illuminate\Support\Facades\Request;
use Larabookir\Gateway\Enum;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;
use Larabookir\Gateway\Samankish\SamankishCallbackException;

class Samankish extends PortAbstract implements PortInterface
{
	/**
	 * Address of main CURL server
	 *
	 * @var string
	 */
	protected $tokenUrl = 'https://sep.shaparak.ir/onlinepg/onlinepg';

	/**
	 * Address of CURL server for verify payment
	 *
	 * @var string
	 */
	protected $serverVerifyUrl = 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://sep.shaparak.ir/OnlinePG/SendToken';

	protected $mobile = '';

	protected $token = '';

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
		return \Redirect::to($this->gateUrl . '?token=' . $this->token);
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
	 * Sets mobile mobile
	 * @param $mobile
	 */
	function setMobile($mobile)
	{
		$this->mobile = $mobile;
		return $this;
	}

	/**
	 * Gets callback url
	 * @return string
	 */
	function getCallback()
	{
		if (!$this->callbackUrl)
			$this->callbackUrl = $this->config->get('gateway.samankish.callback_url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to server
	 *
	 * @return void|boolean
	 *
	 * @throws Exception
	 */
	protected function sendPayRequest()
	{
		$transactionId = $this->newTransaction();

		$params = array(
			"action"      => "token",
			"TerminalId"  => $this->config->get('gateway.samankish.terminal_id'),
			"Amount"      => $this->amount,
			"ResNum"      => $transactionId,
			"RedirectUrl" => $this->getCallback(),
			"CellNumber"  => $this->mobile,
		);

		$response = $this->curlPost($this->tokenUrl, $params);

		if ($response->status === 1) {
			$this->token = $response->token;

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response->errorCode, $response->errorDesc);
		throw new Exception($response->errorDesc . ' # ' . $response->errorCode);
	}

	/**
	 * Check user payment with GET data
	 *
	 * @return bool
	 *
	 * @throws SamankishReceiveException
	 */
	protected function userPayment()
	{
		$this->refIf      = Request::input('RefNum');
		$this->cardNumber = Request::input('SecurePan');
		$trackingCode     = Request::input('TraceNo');
		$state            = Request::input('State');
		$status           = Request::input('Status');

		if ($state == 'OK' && $status == '2' && ((int) $this->amount === (int) Request::input('AffectiveAmount'))) {
			$this->trackingCode = $trackingCode;
			return true;
		}

		$this->transactionFailed();
		$this->newLog($status, SamankishCallbackException::$errors[$status]);
		throw new SamankishCallbackException($status);
	}

	/**
	 * Verify user payment from zarinpal server
	 *
	 * @return bool
	 *
	 * @throws SamankishReceiveException
	 */
	protected function verifyPayment()
	{
		$params = array(
			'TerminalNumber' => $this->config->get('gateway.samankish.terminal_id'),
			'RefNum'         => $this->refIf,
		);

		$response = $this->curlPost($this->serverVerifyUrl, $params);

		if ($response->ResultCode === 0 && $response->Success === true) {
			$this->transactionSucceed();
			$this->newLog($response->ResultCode, Enum::TRANSACTION_SUCCEED_TEXT);

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response->ResultCode, SamankishReceiveException::$errors[$response->ResultCode]);
		throw new SamankishReceiveException($response->ResultCode);
	}

	public function curlPost($url, $params)
	{
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => json_encode($params),
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
				),
			)
		);

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response);
	}
}
