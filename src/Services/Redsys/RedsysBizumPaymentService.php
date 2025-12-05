<?php

namespace Solivellaluisaberto\PayKit\Services\Redsys;

use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;

/**
 * Servicio de pago con Bizum a través de Redsys
 *
 * Esta clase proporciona una implementación concreta del servicio de pago Redsys
 * específicamente para pagos con Bizum. Extiende la clase base `RedsysPaymentService`
 * y configura automáticamente el método de pago como Bizum (`RedsysPaymentMethod::BIZUM`).
 *
 * Bizum es un sistema de pagos móviles instantáneos que permite realizar pagos
 * directamente desde la aplicación móvil del banco del usuario, sin necesidad de
 * introducir datos de tarjeta.
 *
 * Características:
 * - Soporte para pagos con Bizum
 * - Integración con TPV Virtual de Redsys
 * - Generación automática de formularios HTML
 * - Verificación de callbacks con firma de seguridad
 * - Soporte para reembolsos a través de API REST
 * - Pagos instantáneos y seguros
 *
 * Esta clase se instancia automáticamente por el manager `PayKit` cuando se
 * solicita un driver para Redsys con el método de pago 'bizum'.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 * @author Solivellaluisaberto
 *
 * @see RedsysPaymentService Clase base con la implementación común
 * @see RedsysCardPaymentService Servicio para pagos con tarjeta
 */
class RedsysBizumPaymentService extends RedsysPaymentService
{
    /**
     * Constructor de RedsysBizumPaymentService
     *
     * Inicializa una nueva instancia del servicio de pago con Bizum de Redsys.
     * Configura automáticamente el método de pago como Bizum y delega la
     * inicialización de credenciales y configuración a la clase padre.
     *
     * Los parámetros son opcionales y, si no se proporcionan, se obtienen de
     * la configuración de Laravel. Esto permite una configuración flexible
     * tanto a nivel de aplicación como por instancia.
     *
     * @param string|null $merchantCode Código de comercio de Redsys.
     *                                  Si es `null`, se obtiene de `config('pay-kit.redsys.merchant_code')`
     * @param string|null $secretKey Clave secreta de Redsys.
     *                              Si es `null`, se obtiene de `config('pay-kit.redsys.secret_key')`
     * @param string|null $terminal Número de terminal de Redsys.
     *                            Si es `null`, se obtiene de `config('pay-kit.redsys.terminal')` o '1' por defecto
     * @param RedsysEnvironment|string|null $environment Entorno de operación.
     *                                                  Puede ser:
     *                                                  - Un enum `RedsysEnvironment` (recomendado)
     *                                                  - Un string 'test' o 'live'
     *                                                  - `null` para usar `config('pay-kit.redsys.environment')` o 'test' por defecto
     *
     * @throws PaymentConfigurationException Si:
     *                                       - El `$merchantCode` no está configurado (ni como parámetro ni en config)
     *                                       - El `$secretKey` no está configurado (ni como parámetro ni en config)
     *                                       - El `$environment` proporcionado como string no es válido ('test' o 'live')
     *
     * @example
     * ```php
     * // Usando configuración de Laravel
     * $service = new RedsysBizumPaymentService();
     *
     * // Especificando credenciales directamente
     * $service = new RedsysBizumPaymentService(
     *     merchantCode: '999999999',
     *     secretKey: 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
     *     terminal: '1',
     *     environment: RedsysEnvironment::TEST
     * );
     *
     * // Con entorno como string
     * $service = new RedsysBizumPaymentService(
     *     merchantCode: '999999999',
     *     secretKey: 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
     *     environment: 'live'
     * );
     *
     * // Iniciar un pago con Bizum
     * $request = new PaymentRequestData(
     *     amount: 99.99,
     *     currency: Currency::EUR,
     *     orderId: 'ORD-12345',
     *     returnUrl: 'https://example.com/payment/return'
     * );
     *
     * $response = $service->initiate($request);
     * // $response->formHtml contiene el formulario HTML para renderizar
     * // El usuario será redirigido a su app de Bizum para completar el pago
     * ```
     */
    public function __construct(
        ?string $merchantCode = null,
        ?string $secretKey = null,
        ?string $terminal = null,
        RedsysEnvironment|string|null $environment = null
    ) {
        parent::__construct($merchantCode, $secretKey, $terminal, $environment);
        $this->paymentMethod = RedsysPaymentMethod::BIZUM;
    }
}