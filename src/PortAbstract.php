<?php

namespace Larabookir\Gateway;

abstract class PortAbstract
{
    /**
     * Status code for status field in poolport_transactions table
     */
    const TRANSACTION_INIT = 'INIT';
    const TRANSACTION_INIT_TEXT = 'تراکنش ایجاد شد.';

    /**
     * Status code for status field in poolport_transactions table
     */
    const TRANSACTION_SUCCEED = 'SUCCEED';
    const TRANSACTION_SUCCEED_TEXT = 'پرداخت با موفقیت انجام شد.';

    /**
     * Status code for status field in poolport_transactions table
     */
    const TRANSACTION_FAILED = 'FAILED';
    const TRANSACTION_FAILED_TEXT = 'عملیات پرداخت با خطا مواجه شد.';

    /**
     * Transaction id
     *
     * @var null|int
     */
    protected $transactionId = null;

    /**
     * Transaction row in database
     */
    protected $transaction = null;

    /**
     * Customer card number
     *
     * @var string
     */
    protected $cardNumber = '';

    /**
     * @var Config
     */
    protected $config;

    /**
     * Port id
     *
     * @var int
     */
    protected $port;

    /**
     * Reference id
     *
     * @var string
     */
    protected $refId;

    /**
     * Amount in Rial
     *
     * @var int
     */
    protected $amount;

    /**
     * Tracking code payment
     *
     * @var string
     */
    protected $trackingCode;

    /**
     * Initialize of class
     *
     * @param Config $config
     * @param DataBaseManager $db
     * @param int $port
     */
    public function __construct($config, $port)
    {
        $this->config = $config;
        $this->port = $port;
        $this->db = app('db');
    }

    /**
     * @return mixed
     */
    function getTable(){
        return $this->db->table(config('gateway.db_tables.transactions'));
    }

    /**
     * @return mixed
     */
    function getLogTable(){
        return $this->db->table(config('gateway.db_tables.logs'));
    }

    /**
     * Get port id, $this->port
     *
     * @return int
     */
    function getPort()
    {
        return $this->port;
    }

    /**
     * Return card number
     *
     * @return string
     */
    function cardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * Return tracking code
     */
    function trackingCode()
    {
        return $this->trackingCode;
    }

    /**
     * Get transaction id
     *
     * @return int|null
     */
    function transactionId()
    {
        return $this->transactionId;
    }

    /**
     * Return reference id
     */
    function refId()
    {
        return $this->refId;
    }

    /**
     * Return result of payment
     * If result is done, return true, otherwise throws an related exception
     *
     * This method must be implements in child class
     *
     * @param object $transaction row of transaction in database
     *
     * @return $this
     */
    function verify($transaction)
    {
        $this->transaction = $transaction;
        $this->transactionId = intval($transaction->id);
        $this->amount = intval($transaction->price);
        $this->refId = $transaction->ref_id;
    }

    /**
     * Insert new transaction to poolport_transactions table
     *
     * @return int last inserted id
     */
    protected function newTransaction()
    {
        $this->transactionId=$this->getTable()->insert([
            'port'=>$this->port,
            'price'=>$this->amount,
            'status'=>self::TRANSACTION_INIT,
            'created_at'=>time(),
            'updated_at'=>time(),
        ]);

        return $this->transactionId;
    }

    /**
     * Commit transaction
     * Set status field to success status
     *
     * @return bool
     */
    protected function transactionSucceed()
    {
        return $this->getTable()->whereId($this->transactionId)->update([
            'status' => self::TRANSACTION_SUCCEED,
            'tracking_code' => $this->trackingCode,
            'card_number' => $this->cardNumber,
            'payment_date' => time(),
            'updated_at'=>time(),
        ]);
    }

    /**
     * Failed transaction
     * Set status field to error status
     *
     * @return bool
     */
    protected function transactionFailed()
    {
        return   $this->getTable()->whereId($this->transactionId)->update([
            'status'=>self::TRANSACTION_FAILED,
            'updated_at'=>time(),
        ]);
    }

    /**
     * Update transaction refId
     *
     * @return void
     */
    protected function transactionSetRefId()
    {
        return   $this->getTable()->whereId($this->transactionId)->update([
            'ref_id'=>$this->refId,
            'updated_at'=>time(),
        ]);

    }

    /**
     * New log
     *
     * @param string|int $statusCode
     * @param string $statusMessage
     */
    protected function newLog($statusCode, $statusMessage)
    {
        return   $this->getLogTable()->insert([
            'transaction_id'=>$this->transactionId,
            'result_code'=>$statusCode,
            'result_message'=>$statusMessage,
            'log_date'=>time(),
        ]);
    }

    /**
     * Add query string to a url
     *
     * @param string $url
     * @param array $query
     * @return string
     */
    protected function buildQuery($url, array $query)
    {
        $query = http_build_query($query);

        $questionMark = strpos($url, '?');
        if (!$questionMark)
            return "$url?$query";
        else {
            return substr($url, 0, $questionMark + 1).$query."&".substr($url, $questionMark + 1);
        }
    }
}
