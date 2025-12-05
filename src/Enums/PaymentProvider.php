<?php

namespace Solivellaluisaberto\PayKit\Enums;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case REDSYS = 'redsys';
    case PAYPAL = 'paypal';
    case CASH = 'commerce';

    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Stripe',
            self::REDSYS => 'Redsys',
            self::PAYPAL => 'PayPal',
            self::CASH => 'Commerce',
        };
    }
}
