<?php

use Solivellaluisaberto\PayKit\Services\Redsys\RedsysBizumPaymentService;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysCardPaymentService;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysEnvironment;

return [
    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Moneda por defecto para los pagos
    |
    */
    'currency' => env('PAY_KIT_CURRENCY', 'EUR'),


    'logging' => [
        'enabled' => env('PAY_KIT_LOGGING_ENABLED', true),
        'channel' => env('PAY_KIT_LOGGING_CHANNEL', 'payments'),
    ],

    'redsys' => [
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'secret_key' => env('REDSYS_SECRET_KEY'),
        'terminal' => env('REDSYS_TERMINAL'),
        'environment' => env('REDSYS_ENVIRONMENT', 'test')
    ],
];

