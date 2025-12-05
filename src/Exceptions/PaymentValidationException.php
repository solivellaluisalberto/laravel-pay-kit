<?php

namespace Solivellaluisaberto\PayKit\Exceptions;

/**
 * Excepción lanzada cuando los datos de pago no son válidos
 */
class PaymentValidationException extends PaymentException
{
    /**
     * Monto inválido
     */
    public static function invalidAmount(float $amount, string $reason = ''): self
    {
        $message = "Invalid payment amount: {$amount}";
        if ($reason) {
            $message .= " ({$reason})";
        }

        return new self(
            message: $message,
            code: 3001,
            context: [
                'amount' => $amount,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Moneda inválida
     */
    public static function invalidCurrency(string $currency): self
    {
        return new self(
            message: "Invalid currency code: '{$currency}'. Expected ISO 4217 format (e.g., 'EUR', 'USD').",
            code: 3002,
            context: [
                'currency' => $currency,
            ]
        );
    }

    /**
     * Order ID inválido
     */
    public static function invalidOrderId(string $orderId, string $reason = ''): self
    {
        $message = "Invalid order ID: '{$orderId}'";
        if ($reason) {
            $message .= " ({$reason})";
        }

        return new self(
            message: $message,
            code: 3003,
            context: [
                'order_id' => $orderId,
                'reason' => $reason,
            ]
        );
    }

    /**
     * URL de retorno faltante o inválida
     */
    public static function invalidReturnUrl(?string $url): self
    {
        return new self(
            message: "Invalid or missing return URL. A valid return URL is required for this payment method.",
            code: 3004,
            context: [
                'url' => $url,
            ]
        );
    }

    /**
     * Método de pago no soportado
     */
    public static function unsupportedPaymentMethod(string $method, string $provider): self
    {
        return new self(
            message: "Payment method '{$method}' is not supported by {$provider}.",
            code: 3005,
            context: [
                'payment_method' => $method,
                'provider' => $provider,
            ]
        );
    }

    /**
     * Datos requeridos faltantes
     */
    public static function missingRequiredField(string $field): self
    {
        return new self(
            message: "Required field '{$field}' is missing.",
            code: 3006,
            context: [
                'field' => $field,
            ]
        );
    }

    /**
     * Formato de email inválido
     */
    public static function invalidEmail(string $email): self
    {
        return new self(
            message: "Invalid email address: '{$email}'.",
            code: 3007,
            context: [
                'email' => $email,
            ]
        );
    }

    /**
     * Longitud de campo inválida
     */
    public static function invalidFieldLength(string $field, int $actualLength, int $maxLength): self
    {
        return new self(
            message: "Field '{$field}' exceeds maximum length of {$maxLength} characters (actual: {$actualLength}).",
            code: 3008,
            context: [
                'field' => $field,
                'actual_length' => $actualLength,
                'max_length' => $maxLength,
            ]
        );
    }

    /**
     * Validación genérica fallida
     */
    public static function validationFailed(string $field, string $reason): self
    {
        return new self(
            message: "Validation failed for '{$field}': {$reason}",
            code: 3009,
            context: [
                'field' => $field,
                'reason' => $reason,
            ]
        );
    }

    public function getHttpStatusCode(): int
    {
        return 422; // Unprocessable Entity
    }
}

