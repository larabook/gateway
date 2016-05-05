**this package is on developing stage , and it not stabled yet .**

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

Step 2:  

    php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProvider

Step 3: 

    php artisan migrate


Configuration file is placed in config/gateway.php , open it and enter your banks credential:

You can make connection to bank by several way (Facade , Service container):

    try {
    $gateway=app('gateway');
    $refId = $gateway->make(Gateway::MELLAT)->set(1000)->ready()->refId();

    // Your code here

    $gateway->redirect();
    
    } catch (Exception $e) {
	echo $e->getMessage();
    }

Instead of MELLAT you can enter other banks Name as we introduced above .

In `set` method you should enter the price in IRR (RIAL) 

and in your callback :

    try { 
    $gateway=app('gateway');
    $trackingCode = $gateway->verify()->trackingCode();
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
