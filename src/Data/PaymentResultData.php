<?php

namespace Solivellaluisaberto\PayKit\Data;

use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;

/**
 * Data Transfer Object (DTO) para resultados de operaciones de pago
 *
 * Esta clase encapsula el resultado de cualquier operación realizada con un proveedor
 * de pago (capture, refund, getStatus, verifyCallback). Proporciona una estructura
 * estandarizada para representar el estado y los datos de una transacción de pago,
 * independientemente del proveedor utilizado.
 *
 * Todas las propiedades son readonly para garantizar la inmutabilidad del objeto
 * una vez creado, lo que previene modificaciones accidentales durante el procesamiento.
 *
 * La clase incluye validaciones exhaustivas que garantizan la integridad de los datos
 * y la coherencia entre el campo `success` y los identificadores de pago/transacción.
 *
 * @package Solivellaluisaberto\PayKit\Data
 * @author Solivellaluisaberto
 */
class PaymentResultData
{
    /**
     * Indica si la operación fue exitosa
     *
     * Cuando es `true`, indica que la operación se completó correctamente. Cuando es
     * `false`, indica que la operación falló o fue rechazada.
     *
     * Si `success` es `true`, al menos uno de `paymentId` o `transactionId` debe
     * estar presente para identificar la transacción exitosa.
     *
     * @var bool
     */
    public readonly bool $success;

    /**
     * Estado actual del pago
     *
     * Representa el estado de la transacción según el proveedor de pago. Puede ser
     * uno de los estados estándar (pending, processing, completed, failed, etc.) o
     * un estado personalizado específico del proveedor.
     *
     * El estado debe cumplir con el patrón: letras, números, guiones y guiones bajos.
     * Máximo 50 caracteres.
     *
     * @var string
     */
    public readonly string $status;

    /**
     * Identificador único del pago en el proveedor
     *
     * Este ID es proporcionado por el proveedor de pago y se utiliza para realizar
     * operaciones posteriores como consultas de estado, reembolsos o capturas.
     *
     * Puede ser `null` si la operación no generó un pago (ej: validación fallida).
     * Máximo 255 caracteres.
     *
     * @var string|null
     */
    public readonly ?string $paymentId;

    /**
     * Identificador único de la transacción
     *
     * Similar a `paymentId`, pero puede representar una transacción específica
     * dentro de un pago (ej: una captura parcial, un reembolso).
     *
     * Puede ser `null` si no hay una transacción asociada.
     * Máximo 255 caracteres.
     *
     * @var string|null
     */
    public readonly ?string $transactionId;

    /**
     * Mensaje descriptivo del resultado
     *
     * Proporciona información adicional sobre el resultado de la operación, como
     * mensajes de error, confirmaciones o advertencias.
     *
     * Puede ser `null` si no hay mensaje adicional.
     * Máximo 1000 caracteres.
     *
     * @var string|null
     */
    public readonly ?string $message;

    /**
     * Datos adicionales del resultado
     *
     * Array asociativo que puede contener información adicional específica del
     * proveedor, como códigos de error detallados, metadatos de la transacción,
     * o datos de respuesta personalizados.
     *
     * Siempre es un array, nunca `null` (por defecto es un array vacío).
     *
     * @var array<string, mixed>
     */
    public readonly array $data;

    /**
     * Constructor de PaymentResultData
     *
     * Inicializa una nueva instancia de resultado de pago con validación automática
     * de todos los campos. La validación se ejecuta inmediatamente después de la
     * inicialización para garantizar la integridad de los datos.
     *
     * @param bool $success Indica si la operación fue exitosa
     * @param string $status Estado actual del pago (máx. 50 caracteres)
     * @param string|null $paymentId Identificador del pago (máx. 255 caracteres)
     * @param string|null $transactionId Identificador de la transacción (máx. 255 caracteres)
     * @param string|null $message Mensaje descriptivo (máx. 1000 caracteres)
     * @param array<string, mixed> $data Datos adicionales del resultado
     *
     * @throws PaymentValidationException Si algún campo no cumple con las validaciones:
     *                                    - Status vacío o excede longitud máxima
     *                                    - Status contiene caracteres inválidos
     *                                    - Success es true pero no hay paymentId ni transactionId
     *                                    - paymentId, transactionId o message exceden longitud máxima
     *
     * @example
     * ```php
     * // Pago exitoso
     * $result = new PaymentResultData(
     *     success: true,
     *     status: 'completed',
     *     paymentId: 'pay_1234567890',
     *     message: 'Payment processed successfully'
     * );
     *
     * // Pago fallido
     * $result = new PaymentResultData(
     *     success: false,
     *     status: 'failed',
     *     message: 'Insufficient funds'
     * );
     *
     * // Pago pendiente con datos adicionales
     * $result = new PaymentResultData(
     *     success: true,
     *     status: 'pending',
     *     paymentId: 'pay_1234567890',
     *     data: ['requires_3ds' => true, 'redirect_url' => 'https://...']
     * );
     * ```
     */
    public function __construct(
        bool $success,
        string $status,
        ?string $paymentId = null,
        ?string $transactionId = null,
        ?string $message = null,
        array $data = [],
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->paymentId = $paymentId;
        $this->transactionId = $transactionId;
        $this->message = $message;
        $this->data = $data;

        $this->validate();
    }

    /**
     * Valida todos los campos del resultado de pago
     *
     * Ejecuta validaciones exhaustivas para garantizar la integridad de los datos
     * y la coherencia entre los diferentes campos.
     *
     * @return void
     *
     * @throws PaymentValidationException Si alguna validación falla
     */
    private function validate(): void
    {
        $this->validateStatus();
        $this->validateSuccessCoherence();
        $this->validateFieldLengths();
        $this->validateData();
    }

    /**
     * Valida el campo status
     *
     * Verifica que el status no esté vacío, no exceda la longitud máxima y
     * contenga solo caracteres válidos (letras, números, guiones y guiones bajos).
     *
     * @return void
     *
     * @throws PaymentValidationException Si el status es inválido
     */
    private function validateStatus(): void
    {
        $trimmedStatus = trim($this->status);

        if ($trimmedStatus === '') {
            throw PaymentValidationException::validationFailed(
                'status',
                'Status cannot be empty'
            );
        }

        if (strlen($this->status) > 50) {
            throw PaymentValidationException::invalidFieldLength(
                'status',
                strlen($this->status),
                50
            );
        }

        // Validar que solo contenga caracteres alfanuméricos, guiones y guiones bajos
        if (!preg_match('/^[a-z0-9_\-]+$/i', $this->status)) {
            throw PaymentValidationException::validationFailed(
                'status',
                'Status contains invalid characters. Only letters, numbers, hyphens and underscores are allowed'
            );
        }
    }

    /**
     * Valida la coherencia entre success y los identificadores
     *
     * Si `success` es `true`, al menos uno de `paymentId` o `transactionId` debe
     * estar presente para poder identificar la transacción exitosa.
     *
     * @return void
     *
     * @throws PaymentValidationException Si success es true pero no hay identificadores
     */
    private function validateSuccessCoherence(): void
    {
        if ($this->success && $this->paymentId === null && $this->transactionId === null) {
            throw PaymentValidationException::validationFailed(
                'paymentId/transactionId',
                'Successful payment must have either paymentId or transactionId to identify the transaction'
            );
        }
    }

    /**
     * Valida las longitudes máximas de los campos opcionales
     *
     * Verifica que `paymentId`, `transactionId` y `message` no excedan sus
     * respectivas longitudes máximas cuando están presentes.
     *
     * @return void
     *
     * @throws PaymentValidationException Si algún campo excede su longitud máxima
     */
    private function validateFieldLengths(): void
    {
        if ($this->paymentId !== null && strlen($this->paymentId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'paymentId',
                strlen($this->paymentId),
                255
            );
        }

        if ($this->transactionId !== null && strlen($this->transactionId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'transactionId',
                strlen($this->transactionId),
                255
            );
        }

        if ($this->message !== null && strlen($this->message) > 1000) {
            throw PaymentValidationException::invalidFieldLength(
                'message',
                strlen($this->message),
                1000
            );
        }
    }

    /**
     * Valida que el array de datos sea válido
     *
     * Verifica que `$data` sea un array válido y no contenga claves numéricas
     * (debe ser un array asociativo).
     *
     * @return void
     *
     * @throws PaymentValidationException Si el array de datos es inválido
     */
    private function validateData(): void
    {
        if (!is_array($this->data)) {
            throw PaymentValidationException::validationFailed(
                'data',
                'Data must be an array'
            );
        }

        // Verificar que sea un array asociativo (no numérico)
        if (!empty($this->data) && array_keys($this->data) !== range(0, count($this->data) - 1)) {
            // Es un array asociativo, está bien
            return;
        }

        // Si tiene elementos pero es numérico, podría ser válido dependiendo del caso
        // Por ahora, permitimos arrays numéricos también
    }

    /**
     * Verifica si el pago está en un estado de éxito
     *
     * Retorna `true` si el pago está completado o autorizado, indicando que
     * la transacción fue exitosa y el pago está confirmado.
     *
     * @return bool `true` si el pago está completado o autorizado
     */
    public function isCompleted(): bool
    {
        return $this->success && in_array(strtolower($this->status), ['completed', 'authorized'], true);
    }

    /**
     * Verifica si el pago está pendiente
     *
     * Retorna `true` si el pago está en un estado pendiente, indicando que
     * requiere alguna acción adicional o está esperando confirmación.
     *
     * @return bool `true` si el pago está pendiente
     */
    public function isPending(): bool
    {
        return in_array(strtolower($this->status), ['pending', 'processing'], true);
    }

    /**
     * Verifica si el pago falló
     *
     * Retorna `true` si el pago está en un estado de error o fallo, indicando
     * que la transacción no pudo completarse.
     *
     * @return bool `true` si el pago falló
     */
    public function isFailed(): bool
    {
        return !$this->success || in_array(strtolower($this->status), ['failed', 'error', 'cancelled'], true);
    }

    /**
     * Verifica si el pago requiere acción adicional
     *
     * Retorna `true` si el pago está en un estado que requiere alguna acción
     * del usuario o del sistema antes de poder completarse.
     *
     * @return bool `true` si el pago requiere acción adicional
     */
    public function requiresAction(): bool
    {
        return in_array(
            strtolower($this->status),
            [
                'requires_action',
                'requires_payment_method',
                'requires_confirmation',
                'requires_capture',
            ],
            true
        );
    }

    /**
     * Obtiene el identificador principal de la transacción
     *
     * Retorna el `paymentId` si está disponible, de lo contrario retorna el
     * `transactionId`. Si ninguno está disponible, retorna `null`.
     *
     * @return string|null El identificador principal o `null` si no hay ninguno
     */
    public function getIdentifier(): ?string
    {
        return $this->paymentId ?? $this->transactionId;
    }
}
