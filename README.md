
by this  package we are able to connect to all Iranian bank and Paypal with one unique API.

( This Package is now compatible with both **4.\* and 5.\* versions of Laravel** )

Please inform us once you've encountered [bug](https://github.com/hosseinizadeh/gateway/issues) or [issue](https://github.com/hosseinizadeh/gateway/issues)  .

Available Banks:
 1. MELLAT
 2. SADAD (MELLI)
 3. SAMAN
 4. PARSIAN
 5. PASARGAD
 6. ZARINPAL
 7. PAYPAL
 8. ASAN PARDAKHT (update from new documents)
 9. PAY.IR (to use : new \Payir())
 10. YEKPAY (**New**)
----------


**Installation**:

Run below statements on your terminal :

STEP 1 : 

    composer require hosseinizadeh/gateway
    
STEP 2 : Add `provider` and `facade` in config/app.php

    'providers' => [
      ...
      Hosseinizadeh\Gateway\GatewayServiceProvider::class, // <-- add this line at the end of provider array
    ],


    'aliases' => [
      ...
      'Gateway' => Hosseinizadeh\Gateway\Gateway::class, // <-- add this line at the end of aliases array
    ]

Step 3:  

    php artisan vendor:publish --provider=Hosseinizadeh\Gateway\GatewayServiceProvider5

Step 4: 

    php artisan migrate


Configuration file is placed in config/gateway.php , open it and enter your banks credential:

You can make connection to bank by several way (Facade , Service container):

    try {
       
       $gateway = \Gateway::make(new \Mellat());

       // $gateway->setCallback(url('/path/to/callback/route')); You can also change the callback
       $gateway
            ->price(1000)
            // setShipmentPrice(10) // optional - just for paypal
            // setProductName("My Product") // optional - just for paypal
            ->ready();

       $refId =  $gateway->refId(); // شماره ارجاع بانک
       $transID = $gateway->transactionId(); // شماره تراکنش

      // در اینجا
      //  شماره تراکنش  بانک را با توجه به نوع ساختار دیتابیس تان 
      //  در جداول مورد نیاز و بسته به نیاز سیستم تان
      // ذخیره کنید .
      
       return $gateway->redirect();
       
    } catch (\Exception $e) {
       
       	echo $e->getMessage();
    }

you can call the gateway by these ways :
 1. Gateway::make(new Mellat());
 1. Gateway::mellat()
 2. app('gateway')->make(new Mellat());
 3. app('gateway')->mellat();

Instead of MELLAT you can enter other banks Name as we introduced above .

In `price` method you should enter the price in IRR (RIAL) 

and in your callback :

    try { 
       
       $gateway = \Gateway::verify();
       $trackingCode = $gateway->trackingCode();
       $refId = $gateway->refId();
       $cardNumber = $gateway->cardNumber();
       
        // تراکنش با موفقیت سمت بانک تایید گردید
        // در این مرحله عملیات خرید کاربر را تکمیل میکنیم
    
    } catch (\Hosseinizadeh\Gateway\Exceptions\RetryException $e) {
    
        // تراکنش قبلا سمت بانک تاییده شده است و
        // کاربر احتمالا صفحه را مجددا رفرش کرده است
        // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم
        
        echo $e->getMessage() . "<br>";
        
    } catch (\Exception $e) {
       
        // نمایش خطای بانک
        echo $e->getMessage();
    }  


for yekpay you can use like this:

        try {
            $gateway = Gateway::yekpay();

            /// can check country return true or false
            $gateway->checkip(\request()->ip());

            /// can get exchange currency
            $exchange = $gateway->exchange(978,364);

            $gateway->setCallback(route('bank.callback'));
            $gateway->setFrom(364);
            $gateway->setTo(364);
            $gateway->setFname('ftest');
            $gateway->setLname('ltest');
            $gateway->setEmail('test@gmail.com');
            $gateway->setMobile('+44123456789');
            $gateway->setAddress('address');
            $gateway->setPostalcode('9656789');
            $gateway->setCountry('iran');
            $gateway->setCity('tehran');
            $gateway->setDescription('description');
            $gateway->price(10000)->ready(10000);
            $refId = $gateway->refId();
            $transID = $gateway->transactionId();

            $payurl = $gateway->payurl();

            return redirect($payurl);

        } catch (YekpayException $e) {
            $message = $e->getMessage();
            dd($message);
        }
    
If you are intrested to developing this package you can help us by these ways :

 1. Improving documents.
 2. Reporting issue or bugs.
 3. Collaboration in writing codes and other banks modules.

This package is extended from PoolPort  but we've changed some functionality and improved it .
