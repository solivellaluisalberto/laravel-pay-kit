<?php

namespace Solivellaluisaberto\PayKit\Contracts;

use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Data\PaymentResponseData;
use Solivellaluisaberto\PayKit\Data\PaymentResultData;
use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;

/**
 * Contrato para proveedores de pago
 *
 * Esta interfaz define el contrato que deben implementar todos los proveedores
 * de pago (gateways) en el sistema. Establece un conjunto estándar de operaciones
 * que cualquier proveedor debe soportar para garantizar la interoperabilidad
 * y la consistencia en el manejo de pagos.
 *
 * Todas las implementaciones deben garantizar:
 * - Manejo adecuado de excepciones según el tipo de error
 * - Validación de datos de entrada antes de procesar
 * - Retorno de objetos DTO tipados y validados
 * - Manejo seguro de credenciales y datos sensibles
 *
 * @package Solivellaluisaberto\PayKit\Contracts
 * @author Solivellaluisaberto
 */
interface PaymentGateway
{
    /**
     * Inicia un nuevo proceso de pago
     *
     * Este método es el punto de entrada principal para iniciar cualquier transacción
     * de pago. Recibe los datos de la solicitud de pago y retorna la respuesta del
     * proveedor, que puede incluir una URL de redirección, un formulario HTML, o
     * datos para integración mediante API.
     *
     * El tipo de respuesta depende del proveedor y del método de pago utilizado:
     * - **REDIRECT**: URL para redirigir al usuario al proveedor
     * - **FORM**: HTML del formulario a enviar al proveedor
     * - **API**: Datos para integración directa (clientSecret, etc.)
     *
     * @param PaymentRequestData $request Datos de la solicitud de pago, incluyendo:
     *                                    - Monto y moneda
     *                                    - Identificador del pedido
     *                                    - URLs de retorno (success, cancel, error)
     *                                    - Metadatos adicionales
     *
     * @return PaymentResponseData Respuesta del proveedor con la información necesaria
     *                             para completar el pago según el tipo de integración
     *
     * @throws PaymentValidationException Si los datos de la solicitud son inválidos:
     *                                    - Monto inválido o fuera de rango
     *                                    - URLs de retorno inválidas
     *                                    - Moneda no soportada
     *                                    - Identificador de pedido duplicado o inválido
     *
     * @throws PaymentConfigurationException Si falta configuración necesaria:
     *                                       - Credenciales faltantes (merchant_code, secret_key, etc.)
     *                                       - Entorno no configurado
     *                                       - Terminal no configurado
     *
     * @throws PaymentProviderException Si hay un error en la comunicación con el proveedor:
     *                                  - Error de API del proveedor
     *                                  - Error de conexión
     *                                  - Timeout en la solicitud
     *                                  - Respuesta inválida del proveedor
     *
     * @example
     * ```php
     * $request = new PaymentRequestData(
     *     amount: 99.99,
     *     currency: Currency::EUR,
     *     orderId: 'ORD-12345',
     *     successUrl: 'https://example.com/success',
     *     cancelUrl: 'https://example.com/cancel'
     * );
     *
     * $response = $gateway->initiate($request);
     *
     * if ($response->type === PaymentType::REDIRECT) {
     *     return redirect($response->data['redirectUrl']);
     * } elseif ($response->type === PaymentType::FORM) {
     *     return view('payment.form', ['formHtml' => $response->data['formHtml']]);
     * }
     * ```
     */
    public function initiate(PaymentRequestData $request): PaymentResponseData;

    /**
     * Captura o confirma un pago previamente autorizado
     *
     * Este método se utiliza para capturar un pago que fue previamente autorizado
     * pero no capturado. Algunos proveedores (como Stripe) permiten autorizar
     * un pago y capturarlo más tarde, lo que es útil para reservas o pagos que
     * requieren confirmación posterior.
     *
     * Si el pago ya fue capturado o no admite captura diferida, el proveedor
     * debe retornar un resultado apropiado indicando el estado actual.
     *
     * @param string $paymentId Identificador único del pago en el proveedor.
     *                          Este ID fue proporcionado previamente por el proveedor
     *                          en la respuesta de `initiate()` o en un callback.
     *
     * @return PaymentResultData Resultado de la operación de captura, incluyendo:
     *                          - `success`: Indica si la captura fue exitosa
     *                          - `status`: Estado actual del pago después de la captura
     *                          - `paymentId`: ID del pago (puede ser el mismo o uno nuevo)
     *                          - `message`: Mensaje descriptivo del resultado
     *
     * @throws PaymentValidationException Si el `$paymentId` es inválido o está vacío
     *
     * @throws PaymentProviderException Si hay un error en la comunicación con el proveedor:
     *                                  - Pago no encontrado
     *                                  - Pago ya capturado
     *                                  - Pago no puede ser capturado (estado inválido)
     *                                  - Error de API del proveedor
     *                                  - Error de conexión o timeout
     *
     * @example
     * ```php
     * $result = $gateway->capture('pay_1234567890');
     *
     * if ($result->success && $result->isCompleted()) {
     *     // Pago capturado exitosamente
     *     echo "Payment captured: {$result->paymentId}";
     * } else {
     *     // Error en la captura
     *     echo "Capture failed: {$result->message}";
     * }
     * ```
     */
    public function capture(string $paymentId): PaymentResultData;

    /**
     * Reembolsa total o parcialmente un pago
     *
     * Este método permite reembolsar un pago que fue previamente procesado.
     * El reembolso puede ser total (si no se especifica `$amount`) o parcial
     * (si se especifica un monto menor al monto original).
     *
     * No todos los proveedores soportan reembolsos parciales. Si el proveedor
     * no soporta reembolsos parciales y se proporciona un `$amount`, debe lanzar
     * una excepción `PaymentProviderException::operationNotSupported()`.
     *
     * @param string $paymentId Identificador único del pago a reembolsar.
     *                          Este ID fue proporcionado previamente por el proveedor.
     *
     * @param float|null $amount Monto a reembolsar. Si es `null`, se reembolsa
     *                          el monto total del pago. Si se especifica, debe ser:
     *                          - Mayor que 0
     *                          - Menor o igual al monto original del pago
     *                          - En la misma moneda que el pago original
     *
     * @return PaymentResultData Resultado de la operación de reembolso, incluyendo:
     *                          - `success`: Indica si el reembolso fue exitoso
     *                          - `status`: Estado del pago después del reembolso
     *                          - `paymentId`: ID del pago original
     *                          - `transactionId`: ID de la transacción de reembolso (si aplica)
     *                          - `message`: Mensaje descriptivo del resultado
     *
     * @throws PaymentValidationException Si:
     *                                  - El `$paymentId` es inválido o está vacío
     *                                  - El `$amount` es inválido (≤ 0 o mayor al monto original)
     *
     * @throws PaymentProviderException Si hay un error en la comunicación con el proveedor:
     *                                  - Pago no encontrado
     *                                  - Pago no puede ser reembolsado (estado inválido)
     *                                  - Reembolso no disponible (período de reembolso expirado)
     *                                  - Reembolso parcial no soportado (si se especifica `$amount`)
     *                                  - Error de API del proveedor
     *                                  - Error de conexión o timeout
     *
     * @example
     * ```php
     * // Reembolso total
     * $result = $gateway->refund('pay_1234567890');
     *
     * // Reembolso parcial
     * $result = $gateway->refund('pay_1234567890', 50.00);
     *
     * if ($result->success) {
     *     echo "Refund processed: {$result->transactionId}";
     *     if ($result->status === 'partial_refund') {
     *         echo "Partial refund of 50.00";
     *     }
     * }
     * ```
     */
    public function refund(string $paymentId, ?float $amount = null): PaymentResultData;

    /**
     * Obtiene el estado actual de un pago
     *
     * Este método consulta el estado actual de un pago en el proveedor. Es útil
     * para verificar el estado de un pago después de una redirección, para
     * sincronizar el estado local con el estado en el proveedor, o para verificar
     * el estado de un pago asíncrono.
     *
     * El estado retornado debe reflejar el estado más reciente del pago en el
     * proveedor, no un estado en caché local.
     *
     * @param string $paymentId Identificador único del pago a consultar.
     *                          Este ID fue proporcionado previamente por el proveedor.
     *
     * @return PaymentResultData Estado actual del pago, incluyendo:
     *                          - `success`: Indica si la consulta fue exitosa
     *                          - `status`: Estado actual del pago (pending, completed, failed, etc.)
     *                          - `paymentId`: ID del pago consultado
     *                          - `message`: Mensaje descriptivo del estado actual
     *                          - `data`: Datos adicionales del estado (opcional)
     *
     * @throws PaymentValidationException Si el `$paymentId` es inválido o está vacío
     *
     * @throws PaymentProviderException Si hay un error en la comunicación con el proveedor:
     *                                  - Pago no encontrado
     *                                  - Error de API del proveedor
     *                                  - Error de conexión o timeout
     *
     * @example
     * ```php
     * $result = $gateway->getStatus('pay_1234567890');
     *
     * if ($result->isCompleted()) {
     *     echo "Payment completed successfully";
     * } elseif ($result->isPending()) {
     *     echo "Payment is still pending";
     * } elseif ($result->isFailed()) {
     *     echo "Payment failed: {$result->message}";
     * }
     * ```
     */
    public function getStatus(string $paymentId): PaymentResultData;

    /**
     * Verifica y procesa un callback del proveedor
     *
     * Este método se utiliza para procesar las respuestas asíncronas que envían
     * los proveedores de pago después de que el usuario completa o cancela un
     * pago. Es especialmente importante para proveedores que utilizan flujos de
     * redirección o formularios (como Redsys, PayPal).
     *
     * El método debe:
     * 1. Validar la firma/autenticidad del callback (si aplica)
     * 2. Extraer los datos relevantes del callback
     * 3. Retornar un resultado que indique el estado final del pago
     *
     * **Importante**: Este método debe ser llamado desde una ruta pública que
     * reciba los datos POST del proveedor. La validación de la firma es crítica
     * para la seguridad.
     *
     * @param array<string, mixed> $postData Datos POST recibidos del proveedor.
     *                                       El formato exacto depende del proveedor:
     *                                       - **Redsys**: Array con campos como `Ds_Signature`,
     *                                         `Ds_Order`, `Ds_Response`, etc.
     *                                       - **PayPal**: Array con campos de la notificación IPN
     *                                       - **Stripe**: Array con datos del webhook (si aplica)
     *
     * @return PaymentResultData Resultado del procesamiento del callback, incluyendo:
     *                          - `success`: Indica si el pago fue exitoso según el callback
     *                          - `status`: Estado final del pago
     *                          - `paymentId`: ID del pago procesado
     *                          - `transactionId`: ID de la transacción (si aplica)
     *                          - `message`: Mensaje descriptivo del resultado
     *                          - `data`: Datos adicionales del callback
     *
     * @throws PaymentValidationException Si:
     *                                  - Los datos del callback están incompletos
     *                                  - Los datos del callback tienen formato inválido
     *
     * @throws PaymentProviderException Si:
     *                                  - La verificación de firma falla (posible fraude)
     *                                  - El callback contiene datos inconsistentes
     *                                  - Error al procesar el callback
     *
     * @example
     * ```php
     * // En una ruta de callback (ej: Route::post('/payment/callback', ...))
     * public function handleCallback(Request $request)
     * {
     *     $gateway = PayKit::driver('redsys', 'card');
     *     $result = $gateway->verifyCallback($request->all());
     *
     *     if ($result->success && $result->isCompleted()) {
     *         // Pago exitoso, actualizar base de datos
     *         Order::where('payment_id', $result->paymentId)
     *             ->update(['status' => 'paid']);
     *     } else {
     *         // Pago fallido, registrar error
     *         Log::error('Payment failed', ['result' => $result]);
     *     }
     *
     *     return response()->json($result);
     * }
     * ```
     */
    public function verifyCallback(array $postData): PaymentResultData;
}
