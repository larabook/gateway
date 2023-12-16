<?php

namespace Larabookir\Gateway;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Larabookir\Gateway\Exceptions\InvalidRequestException;
use Larabookir\Gateway\Exceptions\NotFoundTransactionException;
use Larabookir\Gateway\Exceptions\PortNotFoundException;
use Larabookir\Gateway\Exceptions\RetryException;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Parsian\Parsian;
use Larabookir\Gateway\Payir\Payir;
use Larabookir\Gateway\Sadad\Sadad;
use Larabookir\Gateway\Saman\Saman;
use Larabookir\Gateway\Zarinpal\Zarinpal;

class GatewayResolver
{

    /**
     * @var Config
     */
    public $config;
    protected $request;
    /**
     * Keep current port driver
     *
     * @var Mellat|Saman|Sadad|Zarinpal|Payir|Parsian
     */
    protected $port;

    /**
     * Gateway constructor.
     * @param null $config
     * @param null $port
     */
    public function __construct($config = null, $port = null)
    {
        $this->config = app('config');
        $this->request = app('request');

        if ($this->config->has('gateway.timezone'))
            date_default_timezone_set($this->config->get('gateway.timezone'));

        if (!is_null($port)) $this->make($port);
    }

    /**
     * Create new object from port class
     *
     * @param int $port
     * @throws PortNotFoundException
     */
    function make($port)
    {
        // Check if $port is an instance of PortAbstract or a string
        if ($port instanceof PortAbstract) {
            // $port is an instance, get the port name from the object
            $portName = $port->getPortName();
            $portKey = strtoupper($portName);
        } else if (is_string($port)) {
            // $port is a string, use it directly
            $portKey = strtoupper($port);
        } else {
            throw new PortNotFoundException("Invalid port type provided.");
        }

        // Check if the port is supported
        $supportedPorts = $this->getSupportedPorts();
        if (!in_array($portKey, $supportedPorts)) {
            throw new PortNotFoundException("Gateway '$portKey' is not supported.");
        }

        // Get the current user from the request
        $user = $this->request->user();
        $userId = $user ? $user->id : null;

        // Define a unique cache key for the gateway configuration
        $cacheKey = "gateway_config_{$portKey}_user_{$userId}";

        // Try to get the configuration from the cache
        $config = Cache::get($cacheKey);

        if (!$config) {
            // Configuration not in cache, fetch from source
            $configSource = $this->config->get('gateway.default', 'file');

            if ($configSource == 'db') {
                // Fetch from database using DB facade, user-specific if user is authenticated
                $query = DB::table('gateway_configurations')
                    ->where(['port' => $portKey, 'key' => 'main']);

                if ($userId) {
                    $query->where('user_id', $userId);
                }

                $gatewayConfig = $query->first();
                $config = $gatewayConfig ? json_decode($gatewayConfig->value, true) : null;
            } else {
                // Fetch from file
                $configKey = "gateway.".strtolower($portKey);
                $config = $this->config->get($configKey);
            }

            if (!$config) {
                throw new PortNotFoundException("Configuration for {$portKey} not found.");
            }

            // Store the configuration in the cache
            Cache::put($cacheKey, $config, 60); // Cache for 60 minutes
        }

        // Create and configure the gateway
        $class = __NAMESPACE__ . '\\' . ucfirst(strtolower($port)) . '\\' . ucfirst(strtolower($port));
        if (class_exists($class)) {
            $this->port = new $class($config);
            $this->port->setPortName($portKey);
            $this->port->setConfig($config);
            $this->port->boot();
        } else {
            throw new PortNotFoundException("Gateway class '{$class}' not found.");
        }

        return $this;
    }

    /**
     * Get supported ports
     *
     * @return array
     */
    public function getSupportedPorts()
    {
        return (array)Enum::getIPGs();
    }

    /**
     * Call methods of current driver
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        // calling by this way ( Gateway::mellat()->.. , Gateway::parsian()->.. )
        if (in_array(strtoupper($name), $this->getSupportedPorts())) {
            return $this->make($name);
        }

        return call_user_func_array([$this->port, $name], $arguments);
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
        if (!$this->request->has('transaction_id') && !$this->request->has('iN'))
            throw new InvalidRequestException;
        if ($this->request->has('transaction_id')) {
            $id = $this->request->get('transaction_id');
        } else {
            $id = $this->request->get('iN');
        }

        $transaction = $this->getTable()->whereId($id)->first();

        if (!$transaction)
            throw new NotFoundTransactionException;

        if (in_array($transaction->status, [Enum::TRANSACTION_SUCCEED, Enum::TRANSACTION_FAILED]))
            throw new RetryException;

        $this->make($transaction->port);

        return $this->port->verify($transaction);
    }

    /**
     * Gets query builder from you transactions table
     * @return mixed
     */
    function getTable()
    {
        return DB::table($this->config->get('gateway.table'));
    }


}
