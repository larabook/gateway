<?php

namespace Larabookir\Gateway;

use Illuminate\Support\Facades\Facade;
use Larabookir\Gateway\Asanpardakht\Asanpardakht;
use Larabookir\Gateway\Irankish\Irankish;
use Larabookir\Gateway\JahanPay\JahanPay;
use Larabookir\Gateway\Mellat\Mellat;
use Larabookir\Gateway\Parsian\Parsian;
use Larabookir\Gateway\Pasargad\Pasargad;
use Larabookir\Gateway\Payir\Payir;
use Larabookir\Gateway\Payline\Payline;
use Larabookir\Gateway\Paypal\Paypal;
use Larabookir\Gateway\Sadad\Sadad;
use Larabookir\Gateway\Saman\Saman;
use Larabookir\Gateway\Zarinpal\Zarinpal;

/**
 * @see \Larabookir\Gateway\GatewayResolver
 * @method  static Mellat|Saman|Sadad|Zarinpal|Payir|Parsian make($port)
 * @method  static Mellat|Saman|Sadad|Zarinpal|Payir|Parsian verify()
 * @method  static Mellat mellat()
 * @method  static Sadad sadad()
 * @method  static Zarinpal zarinpal()
 * @method  static Payline payline()
 * @method  static JahanPay jahanpay()
 * @method  static Parsian parsian()
 * @method  static Pasargad pasargad()
 * @method  static Saman saman()
 * @method  static Asanpardakht asanpardakht()
 * @method  static Paypal paypal()
 * @method  static Payir payir()
 * @method  static Irankish irankish()
 */
class Gateway extends Facade
{
    /**
     * The name of the binding in the IoC container.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'gateway';
    }
}
