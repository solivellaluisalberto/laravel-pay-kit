<?php

namespace Solivellaluisaberto\PayKit\Facades;

use Illuminate\Support\Facades\Facade;
use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;

/**
 * Facade para PayKit
 * 
 * Esta facade proporciona acceso estático a la instancia de PayKit.
 * 
 * @method static PaymentGateway driver(PaymentProvider|string $provider, string|null $method = null) Obtener una instancia del gateway para un proveedor y método específicos
 * @method static void extend(string $name, callable $driver) Registrar un driver personalizado para un proveedor de pago
 * 
 * @see \Solivellaluisaberto\PayKit\PayKit
 * 
 * @example
 * // Obtener un gateway de Redsys para tarjeta
 * $gateway = PayKit::driver(PaymentProvider::REDSYS, 'card');
 * 
 * // Obtener un gateway de Redsys para Bizum
 * $bizumGateway = PayKit::driver(PaymentProvider::REDSYS, 'bizum');
 * 
 * // Registrar un driver personalizado
 * PayKit::extend('mercadopago', function($payKit) {
 *     return new MercadoPagoService(config('payments.mercadopago.key'));
 * });
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
