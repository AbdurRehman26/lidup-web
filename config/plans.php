<?php

return [
    'personal' => [
        'name' => 'Personal',
        'devices' => 1,
        'paddle_price_id' => env('PADDLE_PRICE_PERSONAL'),
    ],
    'pro' => [
        'name' => 'Pro',
        'devices' => 3,
        'paddle_price_id' => env('PADDLE_PRICE_PRO'),
    ],
    'personal_yearly' => [
        'name' => 'Personal Yearly',
        'devices' => 1,
        'paddle_price_id' => env('PADDLE_PRICE_PERSONAL_YEARLY'),
    ],
    'pro_yearly' => [
        'name' => 'Pro Yearly',
        'devices' => 3,
        'paddle_price_id' => env('PADDLE_PRICE_PRO_YEARLY'),
    ],
];
