<?php

return [
    'provider' => env('SMS_PROVIDER', 'log'),
    'itexmo' => [
        'api_code' => env('ITEXMO_API_CODE'),
        'password' => env('ITEXMO_PASSWORD'),
        'email' => env('ITEXMO_EMAIL'),
        'sender' => env('ITEXMO_SENDER'),
    ],
    'clicksend' => [
        'username' => env('CLICKSEND_USERNAME'),
        'api_key' => env('CLICKSEND_API_KEY'),
        'from' => env('CLICKSEND_FROM'),
    ],
    'textbee' => [
        'base_url' => env('TEXTBEE_BASE_URL', 'https://api.textbee.dev'),
        'device_id' => env('TEXTBEE_DEVICE_ID'),
        'api_key' => env('TEXTBEE_API_KEY'),
    ],
];
