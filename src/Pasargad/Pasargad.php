<?php

namespace Larabookir\Gateway\Pasargad;

use Illuminate\Support\Facades\Input;
use Larabookir\Gateway\Enum;
use SoapClient;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\PortInterface;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class Pasargad extends PortAbstract implements PortInterface
{
	/**
	 * Url of parsian gateway web service
	 *
	 * @var string
	 */

	protected $checkTransactionUrl = 'https://pep.shaparak.ir/CheckTransactionResult.aspx';
	protected $verifyUrl = 'https://pep.shaparak.ir/VerifyPayment.aspx';
	protected $refundUrl = 'https://pep.shaparak.ir/doRefund.aspx';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://pep.shaparak.ir/gateway.aspx';

	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = intval($amount);
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

        $processor = new RSAProcessor($this->config->get('gateway.pasargad.certificate-path'),RSAKeyType::XMLFile);

		$url = $this->gateUrl;
		$redirectUrl = $this->getCallback();
        $invoiceNumber = $this->transactionId();
        $amount = $this->amount;
        $terminalCode = $this->config->get('gateway.pasargad.terminalId');
        $merchantCode = $this->config->get('gateway.pasargad.merchantId');
        $timeStamp = date("Y/m/d H:i:s");
        $invoiceDate = date("Y/m/d H:i:s");
        $action = 1003;
        $data = "#". $merchantCode ."#". $terminalCode ."#". $invoiceNumber ."#". $invoiceDate ."#". $amount ."#". $redirectUrl ."#". $action ."#". $timeStamp ."#";
        $data = sha1($data,true);
        $data =  $processor->sign($data); // امضاي ديجيتال
        $sign =  base64_encode($data); // base64_encode

		return view('gateway::pasargad-redirector')->with(compact('url','redirectUrl','invoiceNumber','invoiceDate','amount','terminalCode','merchantCode','timeStamp','action','sign'));
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

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
			$this->callbackUrl = $this->config->get('gateway.pasargad.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to parsian gateway
	 *
	 * @return bool
	 *
	 * @throws ParsianErrorException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();
	}

	/**
	 * Verify payment
	 *
	 * @throws ParsianErrorException
	 */
	protected function verifyPayment()
	{
        $fields = array(
            'invoiceUID' => $_GET['tref']
        );

        $result = Parser::post2https($fields, $this->checkTransactionUrl);
        $array = Parser::makeXMLTree($result);


		if ($array['result'] != "True") {
			$this->newLog(-1, Enum::TRANSACTION_FAILED_TEXT);
			$this->transactionFailed();
			throw new PasargadErrorException(Enum::TRANSACTION_FAILED_TEXT, -1);
		}

        $this->refId = $array['transactionReferenceID'];
        $this->transactionSetRefId();

		$this->trackingCode = $array['traceNumber'];
		$this->transactionSucceed();
		$this->newLog($array['result'], Enum::TRANSACTION_SUCCEED_TEXT);
	}
}
