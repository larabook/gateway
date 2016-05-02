<?php

return array(

    //-------------------------------
    // Timezone for insert dates in database
    // If you want PoolPort not set timezone, just leave it empty
    //--------------------------------
    'timezone' => 'Asia/Tehran',

    //--------------------------------
    // Database configuration
    //--------------------------------
    'database' => array(
        'host'     => '127.0.0.1',
        'dbname'   => '',
        'username' => '',
        'password' => '',
        'create' => true             // For first time you must set this to true for create tables in database
    ),

    //--------------------------------
    // Zarinpal gateway
    //--------------------------------
    'zarinpal' => array(
        'merchant-id'  => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
        'type'         => 'zarin-gate',                           // Types: [zarin-gate || normal]
        'callback-url' => 'http://www.example.org/result',
        'server'       => 'germany',                              // Servers: [germany || iran]
        'email'        => 'email@gmail.com',
        'mobile'       => '09xxxxxxxxx',
        'description'  => 'description',
    ),

    //--------------------------------
    // Mellat gateway
    //--------------------------------
    'mellat' => array(
        'username'     => '',
        'password'     => '',
        'terminalId'   => 0000000,
        'callback-url' => 'http://www.example.org/result'
    ),

    //--------------------------------
    // Payline gateway
    //--------------------------------
    'payline' => array(
        'api' => 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'callback-url' => 'http://www.example.org/result'
    ),

    //--------------------------------
    // Sadad gateway
    //--------------------------------
    'sadad' => array(
        'merchant'      => '',
        'transactionKey'=> '',
        'terminalId'    => 000000000,
        'callback-url'  => 'http://example.org/result'
    ),

    //--------------------------------
    // JahanPay gateway
    //--------------------------------
    'jahanpay' => array(
        'api' => 'xxxxxxxxxxx',
        'callback-url' => 'http://example.org/result'
    ),

    //--------------------------------
    // Parsian gateway
    //--------------------------------
    'parsian' => array(
        'pin'          => 'xxxxxxxxxxxxxxxxxxxx',
        'callback-url' => 'http://example.org/result'
    ),
);
