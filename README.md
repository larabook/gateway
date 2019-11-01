<div dir="rtl">

سایت مرجع پکیج: [larabook.ir](http://larabook.ir/اتصال-درگاه-بانک-لاراول/) 

پکیج اتصال به تمامی IPG ها و  بانک های ایرانی.

این پکیج با ورژن های
(  ۴ و ۵ و ۶ لاراول )
 لاراول سازگار می باشد

درصورت بروز هر گونه 
 [باگ](https://github.com/larabook/gateway/issues) یا [خطا](https://github.com/larabook/gateway/issues)  .
  ما را آگاه سازید .

پشتیبانی تنها از درگاهای زیر می باشد:
 1. MELLAT
 2. SADAD (MELLI)
 3. SAMAN
 4. PARSIAN
 5. PASARGAD
 6. ZARINPAL
 7. PAYPAL (**New**)
 8. ASAN PARDAKHT (**New**)
 9. PAY.IR (**New**) (use 'payir' to make instance)
 10. Irankish (**New**)(use 'irankish' to make instance)
----------


**نصب**:

دستورات زیر را جهت نصب دنبال کنید :

مرحله ۱  : 

    composer require larabook/gateway
    
مرحله ۲  : 

    تغییرات زیر را در فایل  config/app.php اعمال نمایید:

توجه برای نسخه های لاراول ۶ به بعد  این مرحله نیاز به انجام نمی باشد** ** 

```php

'providers' => [
  ...
  Larabookir\Gateway\GatewayServiceProvider::class, // <-- add this line at the end of provider array
],


'aliases' => [
  ...
  'Gateway' => Larabookir\Gateway\Gateway::class, // <-- add this line at the end of aliases array
]

```

مرحله ۳ ( انتقال فایل های مورد نیاز):   

For laravel 5 :

    php artisan vendor:publish --provider=Larabookir\Gateway\GatewayServiceProviderLaravel5

For laravel 6 and up :

    php artisan vendor:publish 

سپس این گزینه را انتخاب کنید :  "Larabookir\Gateway\GatewayServiceProviderLaravel6" 


مرحله ۴ ایجاد جداول: 

    php artisan migrate

مرحله ۵ :
عملیات نصب پایان یافته است حال فایل gateway.php را در مسیر app/ را باز نموده و  تنظیمات مربوط به درگاه بانکی مورد نظر خود را در آن وارد نمایید .

حال میتوایند برای اتصال به api  بانک  از یکی از روش های زیر به انتخاب خودتان استفاده نمایید . (Facade , Service container):

```php

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

```

you can call the gateway by these ways :
 1. Gateway::make(new Mellat())
 2. Gateway::make('mellat')
 3. Gateway::mellat()
 4. app('gateway')->make(new Mellat());
 5. app('gateway')->mellat();

Instead of MELLAT you can enter other banks Name as we introduced above .

In `price` method you should enter the price in IRR (RIAL) 

and in your callback :

```php

try { 

   $gateway = \Gateway::verify();
   $trackingCode = $gateway->trackingCode();
   $refId = $gateway->refId();
   $cardNumber = $gateway->cardNumber();

   // تراکنش با موفقیت سمت بانک تایید گردید
   // در این مرحله عملیات خرید کاربر را تکمیل میکنیم

} catch (\Larabookir\Gateway\Exceptions\RetryException $e) {

    // تراکنش قبلا سمت بانک تاییده شده است و
    // کاربر احتمالا صفحه را مجددا رفرش کرده است
    // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم

    echo $e->getMessage() . "<br>";

} catch (\Exception $e) {

    // نمایش خطای بانک
    echo $e->getMessage();
}

```

If you are intrested to developing this package you can help us by these ways :

 1. Improving documents.
 2. Reporting issue or bugs.
 3. Collaboration in writing codes and other banks modules.

This package is extended from PoolPort  but we've changed some functionality and improved it .
</div>
