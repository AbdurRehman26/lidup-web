<?php

return [
    'personal' => [
        'name' => 'Personal',
        'price' => 4,
        'interval' => 'month',
        'devices' => 1,
        'paddle_price_id' => env('PADDLE_PRICE_PERSONAL'),
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 8,
        'interval' => 'month',
        'devices' => 3,
        'paddle_price_id' => env('PADDLE_PRICE_PRO'),
    ],
];
