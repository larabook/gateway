<?php

namespace Larabookir\Gateway\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $guarded = ['id'];


    public static function zarinpal()
    {
        return self::where('gateway', 'zarinpal')->first();
    }

    public static function mellat()
    {
        return self::where('gateway', 'mellat')->first();
    }

    public static function saman()
    {
        return self::where('gateway', 'saman')->first();
    }

    public static function payir()
    {
        return self::where('gateway', 'payir')->first();
    }

    public static function irankish()
    {
        return self::where('gateway', 'irankish')->first();
    }

    public static function sadad()
    {
        return self::where('gateway', 'sadad')->first();
    }

    public static function parsian()
    {
        return self::where('gateway', 'parsian')->first();
    }

    public static function pasargad()
    {
        return self::where('gateway', 'pasargad')->first();
    }

    public static function asanpardakht()
    {
        return self::where('gateway', 'asanpardakht')->first();
    }

    public static function paypal()
    {
        return self::where('gateway', 'paypal')->first();
    }

}
