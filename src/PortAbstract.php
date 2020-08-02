<?php

namespace Hosseinizadeh\Gateway;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Request;
use Hosseinizadeh\Gateway\Enum;
use Carbon\Carbon;

abstract class PortAbstract
{

    protected $username;
    protected $password;


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
    protected $portName;

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
     * @var
     */
    protected $rrn;

    /**
     * Description of transaction
     *
     * @var string
     */
    protected $description;

    /**
     * callback URL
     *
     * @var url
     */
    protected $callbackUrl;

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
    function __construct()
    {
        $this->db = app('db');
    }

    /** bootstraper */
    function boot()
    {

    }

    function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    function getTable()
    {
        return $this->db->table($this->config->get('gateway.table'));
    }


    /**
     * @return mixed
     */
    function getLogTable()
    {
        return $this->db->table($this->config->get('gateway.table') . '_logs');
    }

    /**
     * Get port id, $this->port
     *
     * @return int
     */
    function getPortName()
    {
        return $this->portName;
    }

    /**
     * Get port id, $this->port
     *
     * @return int
     */
    function setPortName($name)
    {
        $this->portName = $name;
    }

    function setRrn($rrn)
    {
        $this->rrn = $rrn;
    }

    function getRrn()
    {
        return $this->rrn;
    }

    /**
     * Set custom description on current transaction
     *
     * @param string $description
     *
     * @return void
     */
    function setCustomDesc($description)
    {
        $this->description = $description;
    }

    /**
     * Get custom description of current transaction
     *
     * @return string | null
     */
    function getCustomDesc()
    {
        return $this->description;
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
     * Sets price
     * @param $price
     * @return mixed
     */
    function price($price)
    {
        return $this->set($price);
    }

    /**
     * get price
     */
    function getPrice()
    {
        return $this->amount;
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
        $this->transactionId = $transaction->id;
        $this->amount = intval($transaction->price);
        $this->refId = $transaction->ref_id;
    }

    function getTimeId()
    {
        $genuid = function () {
            return substr(str_pad(str_replace('.', '', microtime(true)), 12, 0), 0, 12);
        };
        $uid = $genuid();
        while ($this->getTable()->whereId($uid)->first())
            $uid = $genuid();
        return $uid;
    }

    /**
     * Insert new transaction to poolport_transactions table
     *
     * @return int last inserted id
     */
    protected function newTransaction()
    {
        $uid = $this->getTimeId();


        $this->transactionId = $this->getTable()->insert([
            'id' => $uid,
            'port' => $this->getPortName(),
            'price' => $this->amount,
            'status' => Enum::TRANSACTION_INIT,
            'ip' => Request::getClientIp(),
            'description' => $this->description,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]) ? $uid : null;

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
            'status' => Enum::TRANSACTION_SUCCEED,
            'tracking_code' => $this->trackingCode,
            'card_number' => $this->cardNumber,
            'payment_date' => Carbon::now(),
            'updated_at' => Carbon::now(),
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
        return $this->getTable()->whereId($this->transactionId)->update([
            'status' => Enum::TRANSACTION_FAILED,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Update transaction refId
     *
     * @return void
     */
    protected function transactionSetRefId()
    {
        return $this->getTable()->whereId($this->transactionId)->update([
            'ref_id' => $this->refId,
            'updated_at' => Carbon::now(),
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
        return $this->getLogTable()->insert([
            'transaction_id' => $this->transactionId,
            'result_code' => $statusCode,
            'result_message' => $statusMessage,
            'log_date' => Carbon::now(),
        ]);
    }

    /**
     * Add query string to a url
     *
     * @param string $url
     * @param array $query
     * @return string
     */
    protected function makeCallback($url, array $query)
    {
        return $this->url_modify(array_merge($query, ['_token' => csrf_token()]), url($url));
    }

    /**
     * manipulate the Current/Given URL with the given parameters
     * @param $changes
     * @param  $url
     * @return string
     */
    protected function url_modify($changes, $url)
    {
        // Parse the url into pieces
        $url_array = parse_url($url);

        // The original URL had a query string, modify it.
        if (!empty($url_array['query'])) {
            parse_str($url_array['query'], $query_array);
            $query_array = array_merge($query_array, $changes);
        } // The original URL didn't have a query string, add it.
        else {
            $query_array = $changes;
        }

        return (!empty($url_array['scheme']) ? $url_array['scheme'] . '://' : null) .
            (!empty($url_array['host']) ? $url_array['host'] : null) .
            (!empty($url_array['port']) ? ':' . $url_array['port'] : null) .
            $url_array['path'] . '?' . http_build_query($query_array);
    }

    /**
     * @param $url
     * @param $methods
     * @param array $options
     * @return int|mixed
     */
    protected function clientsPost($url, $methods, $options = array(), $headers = [])
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CUSTOMREQUEST => $methods,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => $options,
                CURLOPT_HTTPHEADER => $headers,
            ));


                $response = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                return [
                    'code' => $code,
                    'result' => $response
                ];

        } catch (\Exception $e) {
            //$err = curl_error($curl);
            $response = $e->getCode();
        }

        return $response;
    }

    

}
