<?php
return [
    'key'         => env('STRIPE_KEY'),
    'secret'      => env('STRIPE_SECRET'),
    'webhook'     => ['secret' => env('STRIPE_WEBHOOK_SECRET'), 'tolerance' => env('CASHIER_WEBHOOK_TOLERANCE', 300)],
    'currency'    => env('CASHIER_CURRENCY', 'usd'),
    'logger'      => env('CASHIER_LOGGER'),
    'payment_urls'=> ['success'=>env('STRIPE_SUCCESS_URL','/'), 'cancel'=>env('STRIPE_CANCEL_URL','/')],
];
