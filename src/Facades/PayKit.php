<?php

namespace Solivellaluisaberto\PayKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed processPayment(float $amount, array $data = [])
 * @method static mixed verifyPayment(string $paymentId)
 *
 * @see \Solivellaluisaberto\PayKit\PayKit
 */
class PayKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pay-kit';
    }
}

