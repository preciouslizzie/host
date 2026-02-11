<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paystack Keys
    |--------------------------------------------------------------------------
    |
    | These values are used to communicate with the Paystack API.
    | You can find them in your Paystack dashboard under “Settings > API Keys”.
    |
    */

    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),
    'secretKey' => env('PAYSTACK_SECRET_KEY'),
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    'merchantEmail' => env('MERCHANT_EMAIL'),
];
