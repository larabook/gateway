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
        'merchant-id'  => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'type'         => 'zarin-gate',             // Types: [zarin-gate || normal]
        'callback-url' => 'http://',
        'server'       => 'germany',                // Servers: [germany || iran]
        'email'        => 'email@gmail.com',
        'mobile'       => '09xxxxxxxxx',
        'description'  => 'description',
    ],

    //--------------------------------
    // Mellat gateway
    //--------------------------------
    'mellat' => [
        'username'     => 'payment18',
        'password'     => '53255911',
        'terminalId'   => 1773870,
        'callback-url' => 'http://h.starsoheil'
    ],

    //--------------------------------
    // Payline gateway
    //--------------------------------
    'payline' => [
        'api' => 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'callback-url' => 'http://'
    ],

    //--------------------------------
    // Sadad gateway
    //--------------------------------
    'sadad' => [
        'merchant'      => '',
        'transactionKey'=> '',
        'terminalId'    => 000000000,
        'callback-url'  => 'http://'
    ],

    //--------------------------------
    // JahanPay gateway
    //--------------------------------
    'jahanpay' => [
        'api' => 'xxxxxxxxxxx',
        'callback-url' => 'http://'
    ],

    //--------------------------------
    // Parsian gateway
    //--------------------------------
    'parsian' => [
        'pin'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => 'http://'
    ],

    //-------------------------------
    // Tables names
    //--------------------------------
    'db_tables' => [
        'transactions' => 'gateway_transactions',
        'logs' => 'gateway_status_log',
    ],
];
