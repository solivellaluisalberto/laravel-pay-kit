<?php

namespace Solivellaluisaberto\PayKit\Services\Redsys;

use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;

/**
 * Servicio de pago redirigido con tarjeta de crédito/débito a través de Redsys
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
class RedsysRedirectPaymentService extends RedsysPaymentService
{
    /**
     * Constructor de RedsysRedirectPaymentService
     *
     * Inicializa el servicio de pago con tarjeta de Redsys usando la
     * configuración cargada desde `config('pay-kit.redsys')`. Establece
     * el método de pago como tarjeta y delega el resto de la inicialización
     * a la clase base.
     *
     * @throws PaymentConfigurationException Si faltan credenciales en configuración
     *                                       o el entorno configurado no es válido.
     *
     * @example
     * ```php
     * // Usando configuración de Laravel
     * $service = new RedsysRedirectPaymentService();
     *
     * // Especificando credenciales directamente
     * $service = new RedsysRedirectPaymentService(
     *     merchantCode: '999999999',
     *     secretKey: 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
     *     terminal: '1',
     *     environment: RedsysEnvironment::TEST
     * );
     *
     * // Con entorno como string
     * $service = new RedsysRedirectPaymentService(
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
    public function __construct() {
        parent::__construct();
        $this->paymentMethod = RedsysPaymentMethod::CARD;
    }
}