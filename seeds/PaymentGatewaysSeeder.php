<?php

use Illuminate\Database\Seeder;
use Larabookir\Gateway\PaymentGateway;
use Larabookir\Gateway\PaymentGatewaySetting;

class PaymentGatewaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $zarinpal = new PaymentGateway();
        $zarinpal::newPort('zarinpal', [
            'merchant-id' => xxxxx-xxxxx-xxxx,
            'type' => 'zarin-gate',
            'callback-url' => 'bank/request/callback',
            'server' => 'germany',
            'email' => 'email@gmail.com',
            'mobile' => '09xxxxxxxxx',
            'description' => 'description',
        ]);

        $mellat = new PaymentGateway();
        $mellat::newPort('mellat', [
            'username' => '',
            'password' => '',
            'terminalId' => '',
            'callback-url' => ''
        ]);

        $saman = new PaymentGateway();
        $saman::newPort('saman', [
            'merchant'     => '',
            'password'     => '',
            'callback-url'   => '/',
        ]);

        $payir = new PaymentGateway();
        $payir::newPort('payir', [
            'api'          => 'xxxxxxxxxxxxxxxxxxxx',
            'callback-url' => '/'
        ]);

        $irankish = new PaymentGateway();
        $irankish::newPort('irankish', [
            'merchantId' => 'xxxxxxxxxxxxxxxxxxxx',
            'sha1key' => 'xxxxxxxxxxxxxxxxxxxx',
            'callback-url' => '/'
        ]);

        $sadad = new PaymentGateway();
        $sadad::newPort('sadad', [
            'merchant'      => '',
            'transactionKey'=> '',
            'terminalId'    => 000000000,
            'callback-url'  => '/'
        ]);

        $parsian = new PaymentGateway();
        $parsian::newPort('parsian', [
            'pin'          => 'xxxxxxxxxxxxxxxxxxxx',
            'callback-url' => '/'
        ]);

        $pasargad = new PaymentGateway();
        $pasargad::newPort('pasargad', [
            'terminalId'    => 000000,
            'merchantId'    => 000000,
            'certificate-path'    => storage_path('gateway/pasargad/certificate.xml'),
            'callback-url' => '/gateway/callback/pasargad'
        ]);

        $asanpardakht = new PaymentGateway();
        $asanpardakht::newPort('asanpardakht', [
            'merchantId'     => '',
            'merchantConfigId'     => '',
            'username' => '',
            'password' => '',
            'key' => '',
            'iv' => '',
            'callback-url'   => '/',
        ]);

        $paypal = new PaymentGateway();
        $paypal::newPort('paypal', [
            'default_product_name' => 'My Product',
            'default_shipment_price' => 0,
            'client_id' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'secret'    => 'xxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'settings'  => [
                'mode'                   => 'sandbox',
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled'         => true,
                'log.FileName'           => storage_path() . '/logs/paypal.log',

                'call_back_url'          => '/gateway/callback/paypal',
                'log.LogLevel'           => 'FINE'

            ]
        ]);

    }
}
