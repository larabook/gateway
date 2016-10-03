<?php

namespace Larabookir\Gateway;

interface PortInterface
{

    /**
     * This method use for set price in Rial.
     *
     * @param int $amount in Rial
     *
     * @return $this
     */
    public function set($amount);

    /**
     * This method use for done everything that necessary before redirect to port.
     *
     * @return $this
     */
    public function ready();

    /**
     * Get ref id, in some ports ref id has a different name such as authority
     *
     * @return int|string
     */
    public function refId();

    /**
     * Return tracking code
     *
     * @return int|string
     */
    public function trackingCode();

    /**
     * Get port id, $this->portId
     *
     * @return int
     */
    public function getPortName();

    /**
     * Get transaction id
     *
     * @return int|null
     */
    public function transactionId();

    /**
     * Return card number
     *
     * @return string
     */
    public function cardNumber();

    /**
     * Sets callback url
     *
     * @return string
     */
    public function setCallback($url);

    /**
     * Gets callback url
     *
     * @return string
     */
    public function getCallback();

    /**
     * This method use for redirect to port
     *
     * @return mixed
     */
    public function redirect();

    /**
     * Return result of payment
     * If result is done, return true, otherwise throws an related exception
     *
     * @param object $transaction row of transaction in database
     *
     * @return $this
     */
    public function verify($transaction);
}
