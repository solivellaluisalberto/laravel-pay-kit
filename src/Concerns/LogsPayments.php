<?php

namespace Solivellaluisaberto\PayKit\Concerns;

use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Data\PaymentResponseData;
use Solivellaluisaberto\PayKit\Data\PaymentResultData;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Illuminate\Support\Facades\Log;

/**
 * Trait para logging de operaciones de pago
 *
 * Este trait proporciona métodos para registrar todas las operaciones relacionadas
 * con pagos, incluyendo intentos de pago, respuestas, resultados, errores, reembolsos
 * y verificaciones de estado. Facilita el debugging y la auditoría de transacciones.
 *
 * El logging puede ser habilitado o deshabilitado mediante la configuración
 * 'pay-kit.logging.enabled', y el canal de logging puede ser configurado mediante
 * 'pay-kit.logging.channel'.
 *
 * @package Solivellaluisaberto\PayKit\Concerns
 * @author Solivellaluisaberto
 */
trait LogsPayments
{
    /**
     * Obtiene el canal de logging configurado
     *
     * Retorna el nombre del canal de logging que debe utilizarse para registrar
     * las operaciones de pago. Si no está configurado, utiliza 'payments' por defecto.
     *
     * @return string Nombre del canal de logging
     */
    protected function getLogChannel(): string
    {
        return config('pay-kit.logging.channel', 'payments');
    }

    /**
     * Verifica si el logging está habilitado
     *
     * Retorna `true` si el logging de pagos está habilitado en la configuración,
     * `false` en caso contrario. Por defecto, el logging está habilitado.
     *
     * @return bool `true` si el logging está habilitado, `false` en caso contrario
     */
    protected function isLoggingEnabled(): bool
    {
        return config('pay-kit.logging.enabled', true);
    }

    /**
     * Registra un intento de pago
     *
     * Registra información sobre un intento de pago antes de ser procesado por el
     * proveedor. Incluye información básica como el proveedor, monto, moneda y
     * identificador del pedido.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentRequestData $request Datos de la solicitud de pago
     *
     * @return void
     */
    protected function logPaymentAttempt(PaymentProvider $provider, PaymentRequestData $request): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment attempt', [
            'provider' => $provider->value,
            'amount' => $request->amount,
            'currency' => $request->currency->value,
            'order_id' => $request->orderId,
            'return_url' => $request->returnUrl,
            'cancel_url' => $request->cancelUrl,
            'notification_url' => $request->notificationUrl,
            'has_metadata' => !empty($request->metadata),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra que un pago ha sido iniciado
     *
     * Registra información sobre la respuesta del proveedor después de iniciar
     * un pago. Incluye el tipo de respuesta y qué campos están presentes.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentRequestData $request Datos de la solicitud de pago original
     * @param PaymentResponseData $response Respuesta del proveedor de pago
     *
     * @return void
     */
    protected function logPaymentInitiated(
        PaymentProvider $provider,
        PaymentRequestData $request,
        PaymentResponseData $response
    ): void {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment initiated', [
            'provider' => $provider->value,
            'order_id' => $request->orderId,
            'amount' => $request->amount,
            'currency' => $request->currency->value,
            'type' => $response->type->value,
            'has_redirect_url' => $response->redirectUrl !== null,
            'has_client_secret' => $response->clientSecret !== null,
            'has_form_html' => $response->formHtml !== null,
            'data_keys' => array_keys($response->data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un pago exitoso
     *
     * Registra información sobre un pago que se completó exitosamente. Incluye
     * los identificadores del pago y la transacción, el estado y cualquier mensaje.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentResultData $result Resultado del pago
     *
     * @return void
     */
    protected function logPaymentSuccess(PaymentProvider $provider, PaymentResultData $result): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Payment successful', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'has_data' => !empty($result->data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un pago fallido
     *
     * Registra información sobre un pago que falló. Se registra como warning ya
     * que es un evento esperado en algunos casos (pago rechazado, fondos insuficientes, etc.).
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentResultData $result Resultado del pago fallido
     *
     * @return void
     */
    protected function logPaymentFailed(PaymentProvider $provider, PaymentResultData $result): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->warning('Payment failed', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'has_data' => !empty($result->data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un error durante el procesamiento de un pago
     *
     * Registra información detallada sobre una excepción que ocurrió durante el
     * procesamiento de un pago. Incluye el mensaje de error, la clase de la excepción,
     * el código de error y la ubicación donde ocurrió.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param \Throwable $exception Excepción que ocurrió
     * @param string|null $orderId Identificador del pedido (opcional)
     *
     * @return void
     */
    protected function logPaymentError(
        PaymentProvider $provider,
        \Throwable $exception,
        ?string $orderId = null
    ): void {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->error('Payment error', [
            'provider' => $provider->value,
            'order_id' => $orderId,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un intento de reembolso
     *
     * Registra información sobre un intento de reembolso antes de ser procesado.
     * Incluye si es un reembolso total o parcial.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param string $paymentId Identificador del pago a reembolsar
     * @param float|null $amount Monto a reembolsar. Si es `null`, es un reembolso total
     *
     * @return void
     */
    protected function logRefundAttempt(
        PaymentProvider $provider,
        string $paymentId,
        ?float $amount = null
    ): void {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Refund attempt', [
            'provider' => $provider->value,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'full_refund' => $amount === null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un reembolso exitoso
     *
     * Registra información sobre un reembolso que se completó exitosamente.
     * Incluye el identificador de la transacción de reembolso y el estado.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentResultData $result Resultado del reembolso
     *
     * @return void
     */
    protected function logRefundSuccess(PaymentProvider $provider, PaymentResultData $result): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Refund successful', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'has_data' => !empty($result->data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra un reembolso fallido
     *
     * Registra información sobre un reembolso que falló. Se registra como warning
     * ya que es un evento esperado en algunos casos (pago ya reembolsado, período
     * de reembolso expirado, etc.).
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param PaymentResultData $result Resultado del reembolso fallido
     *
     * @return void
     */
    protected function logRefundFailed(PaymentProvider $provider, PaymentResultData $result): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->warning('Refund failed', [
            'provider' => $provider->value,
            'payment_id' => $result->paymentId,
            'transaction_id' => $result->transactionId,
            'status' => $result->status,
            'message' => $result->message,
            'has_data' => !empty($result->data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra una consulta de estado de pago
     *
     * Registra información sobre una consulta del estado de un pago. Se registra
     * como debug ya que es una operación frecuente y de bajo nivel.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param string $paymentId Identificador del pago consultado
     *
     * @return void
     */
    protected function logStatusCheck(PaymentProvider $provider, string $paymentId): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->debug('Status check', [
            'provider' => $provider->value,
            'payment_id' => $paymentId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra la recepción de un callback del proveedor
     *
     * Registra información sobre un callback recibido del proveedor de pago.
     * Solo registra las claves de los datos recibidos por seguridad, no los valores
     * completos que pueden contener información sensible.
     *
     * @param PaymentProvider $provider Proveedor de pago utilizado
     * @param array<string, mixed> $data Datos recibidos en el callback
     *
     * @return void
     */
    protected function logCallbackReceived(PaymentProvider $provider, array $data): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->info('Callback received', [
            'provider' => $provider->value,
            'data_keys' => array_keys($data),
            'data_count' => count($data),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Registra una métrica personalizada
     *
     * Permite registrar métricas personalizadas relacionadas con pagos. Útil para
     * tracking de eventos específicos o métricas de negocio.
     *
     * @param string $metric Nombre de la métrica a registrar
     * @param array<string, mixed> $data Datos adicionales de la métrica
     *
     * @return void
     */
    protected function logMetric(string $metric, array $data = []): void
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        Log::channel($this->getLogChannel())->debug($metric, array_merge($data, [
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}

