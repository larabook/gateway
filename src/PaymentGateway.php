<?php

namespace Larabookir\Gateway;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $guarded = [];

    public function settings()
    {
        return $this->hasMany(PaymentGatewaySetting::class);
    }

    public static function newPort($name, $settings)
    {
        try {
            $paymentGateway = new self();
            $paymentGateway->name = $name;
            $paymentGateway->save();

            foreach ($settings as $key => $value)
            {
                $paymentGatewaySetting = new PaymentGatewaySetting();
                $paymentGatewaySetting->payment_gateway_id = $paymentGateway->id;
                $paymentGatewaySetting->key = $key;
                $paymentGatewaySetting->value = $value;
                $paymentGatewaySetting->save();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
