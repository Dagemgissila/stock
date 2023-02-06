<?php
return [
    'mailgun'  => ['domain'=>env('MAILGUN_DOMAIN'),'secret'=>env('MAILGUN_SECRET'),'endpoint'=>env('MAILGUN_ENDPOINT','api.mailgun.net')],
    'postmark' => ['token'=>env('POSTMARK_TOKEN')],
    'ses'      => ['key'=>env('AWS_ACCESS_KEY_ID'),'secret'=>env('AWS_SECRET_ACCESS_KEY'),'region'=>env('AWS_DEFAULT_REGION','us-east-1')],
    'paypal'   => ['client_id'=>env('PAYPAL_CLIENT_ID'),'secret'=>env('PAYPAL_SECRET'),'mode'=>env('PAYPAL_MODE','sandbox')],
    'razorpay' => ['key_id'=>env('RAZORPAY_KEY_ID'),'key_secret'=>env('RAZORPAY_KEY_SECRET')],
    'mollie'   => ['key'=>env('MOLLIE_KEY')],
    'paystack' => ['public_key'=>env('PAYSTACK_PUBLIC_KEY'),'secret_key'=>env('PAYSTACK_SECRET_KEY'),'payment_url'=>env('PAYSTACK_PAYMENT_URL','https://api.paystack.co')],
];
