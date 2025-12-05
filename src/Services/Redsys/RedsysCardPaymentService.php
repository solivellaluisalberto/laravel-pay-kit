<?php

namespace Solivellaluisaberto\PayKit\Services\Redsys;

use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;

/**
 * Servicio de pago con tarjeta de crédito/débito a través de Redsys
 *
 * Esta clase proporciona una implementación concreta del servicio de pago Redsys
 * específicamente para pagos con tarjeta de crédito o débito. Extiende la clase
 * base `RedsysPaymentService` y configura automáticamente el método de pago como
 * tarjeta (`RedsysPaymentMethod::CARD`).
 *
 * Características:
 * - Soporte para tarjetas de crédito y débito
 * - Integración con TPV Virtual de Redsys
 * - Generación automática de formularios HTML
 * - Verificación de callbacks con firma de seguridad
 * - Soporte para reembolsos a través de API REST
 *
 * Esta clase se instancia automáticamente por el manager `PayKit` cuando se
 * solicita un driver para Redsys con el método de pago 'card'.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 * @author Solivellaluisaberto
 *
 * @see RedsysPaymentService Clase base con la implementación común
 * @see RedsysBizumPaymentService Servicio para pagos con Bizum
 */
class RedsysCardPaymentService extends RedsysPaymentService
{
    /**
     * Constructor de RedsysCardPaymentService
     *
     * Inicializa una nueva instancia del servicio de pago con tarjeta de Redsys.
     * Configura automáticamente el método de pago como tarjeta y delega la
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
     * $service = new RedsysCardPaymentService();
     *
     * // Especificando credenciales directamente
     * $service = new RedsysCardPaymentService(
     *     merchantCode: '999999999',
     *     secretKey: 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
     *     terminal: '1',
     *     environment: RedsysEnvironment::TEST
     * );
     *
     * // Con entorno como string
     * $service = new RedsysCardPaymentService(
     *     merchantCode: '999999999',
     *     secretKey: 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
     *     environment: 'live'
     * );
     *
     * // Iniciar un pago
     * $request = new PaymentRequestData(
     *     amount: 99.99,
     *     currency: Currency::EUR,
     *     orderId: 'ORD-12345',
     *     returnUrl: 'https://example.com/payment/return'
     * );
     *
     * $response = $service->initiate($request);
     * // $response->formHtml contiene el formulario HTML para renderizar
     * ```
     */
    public function __construct(
        ?string $merchantCode = null,
        ?string $secretKey = null,
        ?string $terminal = null,
        RedsysEnvironment|string|null $environment = null
    ) {
        parent::__construct($merchantCode, $secretKey, $terminal, $environment);
        $this->paymentMethod = RedsysPaymentMethod::CARD;
    }
}