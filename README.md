**this package is on developing stage , and it's not stabled yet .**

package's home : [larabook.ir](http://larabook.ir) 

by this  package we are able to connect to all Iranian bank with one unique API.

Please inform us once you've encountered [bug](https://github.com/larabook/gateway/issues) or [issue](https://github.com/larabook/gateway/issues)  .

Available Banks:
 1. MELLAT
 2. SADAD
 3. PARSIAN
 4. ZARINPAL
 5. JAHANPAY
 6. PAYLINE

SAMAN bank will be added asap.
 


----------


**Installation**:

Run below statements on your terminal :

STEP 1 : 

    composer require poolport/poolport:~v3
    
STEP 2 : Add `provider` and `facade` in config/app.php

    'providers' => [
      ...
      Larabookir\Gateway\GatewayServiceProvider::class, // <-- add this line at the end of provider array
    ],


    'aliases' => [
      ...
      'Gateway' => \Larabookir\Gateway\Gateway::class, // <-- add this line at the end of aliases array
    ]

Step 3:  

    php artisan vendor:publish --provider="Larabookir\Gateway\GatewayServiceProvider"

Step 4: 

    php artisan migrate


Configuration file is placed in config/gateway.php , open it and enter your banks credential:

You can make connection to bank by several way (Facade , Service container):

    try {
       
       $gateway = Gateway::make(new Mellat());
       // $gateway->setCallback(url('/path/to/calback/route')); You can also change the callback
       $gateway->price(1000)->ready();
       $refId =  $gateway->refId();
       $transID = $gateway->transactionId();

       // Your code here

       $gateway->redirect();
       
    } catch (Exception $e) {
       
       	echo $e->getMessage();
    }

you can call the gateway by these ways :
 1. Gateway::make(new Mellat());
 1. Gateway::mellat()
 2. app('gateway')->make(new Mellat());
 3. app('gateway')->mellat();

Instead of MELLAT you can enter other banks Name as we introduced above .

In `set` method you should enter the price in IRR (RIAL) 

and in your callback :

    try { 
       
       $gateway = Gateway::verify();
       $trackingCode = $gateway->trackingCode();
       $refId = $gateway->refId();
       $cardNumber = $gateway->cardNumber();
       
       // Your code here
       
    } catch (Exception $e) {
       
       echo $e->getMessage();
    }  

If you are intrested to developing this package you can help us by these ways :

 1. Improving documents.
 2. Reporting issue or bugs.
 3. Collaboration in writing codes and other banks modules.

This package is extended from PoolPort  but we've changed some functionality and improved it .
