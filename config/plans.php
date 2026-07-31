<?php

return [
    'personal' => [
        'name' => 'Personal Lifetime',
        'devices' => 1,
        'paddle_price_id' => env('PADDLE_PRICE_PERSONAL_LIFETIME'),
    ],
    'pro' => [
        'name' => 'Pro Lifetime',
        'devices' => 3,
        'paddle_price_id' => env('PADDLE_PRICE_PRO_LIFETIME'),
    ],
];
