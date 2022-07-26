<?php

return [

    //-------------------------------
    // Timezone for insert dates in database
    // If you want Gateway not set timezone, just leave it empty
    //--------------------------------
    'timezone' => 'Asia/Tehran',

    //--------------------------------
    // Zarinpal gateway
    //--------------------------------
    'zarinpal' => [
        'merchant-id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'type' => 'zarin-gate',             // Types: [zarin-gate || normal]
        'callback-url' => '/',
        'server' => 'iran',                   // Servers: [germany || iran || test]
        'email' => 'email@gmail.com',
        'mobile' => '09xxxxxxxxx',
        'description' => 'description',
    ],

    //--------------------------------
    // Zarinpal wages gateway
    //--------------------------------
    'zarinpalwages' => [
        'merchant-id' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'type' => 'zarin-gate',             // Types: [zarin-gate || normal]
        'callback-url' => '/',
        'server' => 'iran',                   // Servers: [germany || iran || test]
        'email' => 'email@gmail.com',
        'mobile' => '09xxxxxxxxx',
        'description' => 'description',

        'iban' => 'IRxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'iban2' => 'IRxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        /// and other iban
    ],

    //--------------------------------
    // Novin gateway
    //--------------------------------
    'novin' => [
        'username' => '',
        'password' => '',
        'signature' => true,
        'certificate_password' => '',
        'certificate_path' => storage_path('gateway/novin/PardakhtNovin.pem'),
        'temp_files_dir' => storage_path('gateway/novin'),
        'callback-url' => '/'
    ],

    //--------------------------------
    // Mellat gateway
    //--------------------------------
    'mellat' => [
        'username' => '',
        'password' => '',
        'terminalId' => 0000000,
        'callback-url' => '/'
    ],

    //--------------------------------
    // Saman gateway
    //--------------------------------
    'saman' => [
        'merchant' => '',
        'password' => '',
        'callback-url' => '/',
    ],

    //--------------------------------
    // PayIr gateway
    //--------------------------------
    'payir' => [
        'api' => 'test',
        'callback-url' => '/'
    ],

    //--------------------------------
    // Sadad gateway
    //--------------------------------
    'sadad' => [
        'merchant' => '',
        'transactionKey' => '',
        'terminalId' => 000000000,
        'callback-url' => '/'
    ],

    //--------------------------------
    // Parsian gateway
    //--------------------------------
    'parsian' => [
        'pin' => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => '/'
    ],
    //--------------------------------
    // Pasargad gateway
    //--------------------------------
    'pasargad' => [
        'terminalId' => 000000,
        'merchantId' => 000000,
        'certificate-path' => storage_path('gateway/pasargad/certificate.xml'),
        'callback-url' => '/gateway/callback/pasargad'
    ],

    //--------------------------------
    // Asan Pardakht gateway
    //--------------------------------
    'asanpardakht' => [
        'merchantId' => '',
        'merchantConfigId' => '',
        'username' => '',
        'password' => '',
        'iban' => '',
        'key' => '',
        'iv' => '',
        'callback-url' => '/',
    ],

    //--------------------------------
    // Yekpay gateway
    //--------------------------------
    'yekpay' => [
        'merchantId' => 'test',
        'callback-url' => '/',
    ],

    //--------------------------------
    // Paypal gateway
    //--------------------------------
    'paypal' => [
        // Default product name that appear on paypal payment items
        'default_product_name' => 'My Product',
        'default_shipment_price' => 0,
        // set your paypal credential
        'client_id' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'secret' => 'xxxxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'settings' => [
            'mode' => 'sandbox', //'sandbox' or 'live'
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => true,
            'log.FileName' => storage_path() . '/logs/paypal.log',
            /**
             * Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
             *
             * Logging is most verbose in the 'FINE' level and decreases as you
             * proceed towards ERROR
             */
            'call_back_url' => '/gateway/callback/paypal',
            'log.LogLevel' => 'FINE'

        ]
    ],
    //-------------------------------
    // Tables names
    //--------------------------------
    'table' => 'gateway_transactions',
];
