<?php

namespace Larabookir\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
class RetryException extends \Exception {}
