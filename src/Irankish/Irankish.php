<?php

namespace Larabookir\Gateway\Irankish;

use DateTime;
use Illuminate\Support\Facades\Input;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;

class Irankish extends PortAbstract implements PortInterface
{
	/**
	 * Address of main SOAP server
	 *
	 * @var string
	 */
	protected $serverUrl = 'https://ikc.shaparak.ir/XToken/Tokens.xml';
	protected $serverVerifyUrl = "https://ikc.shaparak.ir/XVerify/Verify.xml";
	
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
		$refId      = $this->refId;
		$merchantId = $this->config->get('gateway.irankish.merchantId');
		
		return view('gateway::irankish-redirector')->with(compact('refId', 'merchantId'));
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
	 *
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
		if (!$this->callbackUrl) {
			$this->callbackUrl = $this->config->get('gateway.irankish.callback-url');
		}
		
		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}
	
	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws IranKishException
	 */
	protected function sendPayRequest()
	{
		$dateTime = new DateTime();
		
		$this->newTransaction();
		
		$fields = [
			'amount'           => $this->amount,
			'merchantId'       => $this->config->get('gateway.irankish.merchantId'),
			'invoiceNo'        => time(),
			'paymentId'        => time(),
			'specialPaymentId' => '',
			'revertURL'        => $this->getCallback(),
			'description'      => '',
		];
		
		try {
			$soap     = new SoapClient($this->serverUrl, ['soap_version' => SOAP_1_1]);
			$response = $soap->MakeToken($fields);
			
		} catch (\SoapFault $e) {
			$this->transactionFailed();
			$this->newLog('SoapFault', $e->getMessage());
			throw $e;
		}
		
		if ($response->MakeTokenResult->result != true) {
			$this->transactionFailed();
			$this->newLog($response->MakeTokenResult->message, IrankishException::$errors[$response[0]]);
			throw new IranKishException($response[0]);
		}
		$this->refId = $response->MakeTokenResult->token;
		$this->transactionSetRefId();
	}
	
	/**
	 * Check user payment
	 *
	 * @return bool
	 *
	 * @throws IranKishException
	 */
	protected function userPayment()
	{
		
		$this->refId        = Input::get('token');
		$this->trackingCode = Input::get('referenceId');
		$this->cardNumber   = Input::get('cardNo');
		$payRequestResCode  = Input::get('resultCode');
		
		if ($payRequestResCode == '100') {
			return true;
		}
		
		$this->transactionFailed();
		$this->newLog($payRequestResCode, @IrankishException::$errors[$payRequestResCode]);
		throw new IrankishException($payRequestResCode);
	}
	
	/**
	 * Verify user payment from bank server
	 *
	 * @return bool
	 *
	 * @throws IranKishException
	 * @throws SoapFault
	 */
	protected function verifyPayment()
	{
		$fields = [
			'token'       => $this->transactionId(),
			'merchantId'  => $this->config->get('gateway.irankish.merchantId'),
			'referenceNumber' => $this->trackingCode(),
			'sha1key'         => $this->config->get('gateway.irankish.sha1key')
		];
		
	
		try {
			$soap     = new SoapClient($this->serverVerifyUrl);
			$response = $soap->KicccPaymentsVerification($fields);
		
		} catch (\SoapFault $e) {
			$this->transactionFailed();
			$this->newLog('SoapFault', $e->getMessage());
			throw $e;
		}
		
		if ($response->KicccPaymentsVerificationResult  != $this->amount) {
			$this->transactionFailed();
			$this->newLog($response->KicccPaymentsVerificationResult, IrankishException::$errors[$response->KicccPaymentsVerificationResult]);
			throw new IrankishException($response->KicccPaymentsVerificationResult);
		}
		
		$this->transactionSucceed();
		$this->newLog($response->KicccPaymentsVerificationResult, Enum::TRANSACTION_SUCCEED_TEXT);
		
		
		return true;
	}
	
}
