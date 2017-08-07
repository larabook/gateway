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
		'callback-url' => '/',
		'server'       => 'germany',                // Servers: [germany || iran || test]
		'email'        => 'email@gmail.com',
		'mobile'       => '09xxxxxxxxx',
		'description'  => 'description',
	],

	//--------------------------------
	// Mellat gateway
	//--------------------------------
	'mellat'   => [
		'username'     => '',
		'password'     => '',
		'terminalId'   => 0000000,
		'callback-url' => '/'
	],

	//--------------------------------
	// Payline gateway
	//--------------------------------
	'payline'  => [
		'api'          => 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxx',
		'callback-url' => '/'
	],

	//--------------------------------
	// Sadad gateway
	//--------------------------------
	'sadad'    => [
		'merchant'       => '',
		'transactionKey' => '',
		'terminalId'     => 000000000,
		'callback-url'   => '/'
	],

	//--------------------------------
	// JahanPay gateway
	//--------------------------------
	'jahanpay' => [
		'api'          => 'xxxxxxxxxxx',
		'callback-url' => '/'
	],

	//--------------------------------
	// Parsian gateway
	//--------------------------------
	'parsian'  => [
		'pin'          => 'xxxxxxxxxxxxxxxxxxxx',
		'callback-url' => '/'
	],
	//--------------------------------
	// Pasargad gateway
	//--------------------------------
	'pasargad' => [
		'isTwoStep'        => false,
		'terminalId'       => 000000,
		'merchantId'       => 000000,
		'certificate-path' => storage_path('gateway/pasargad/certificate.xml'),
		'callback-url'     => '/gateway/callback/pasargad'
	],
	//-------------------------------
	// Tables names
	//--------------------------------
	'table'    => 'gateway_transactions',
];
