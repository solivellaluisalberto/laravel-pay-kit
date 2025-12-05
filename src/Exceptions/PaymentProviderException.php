<?php

namespace Solivellaluisaberto\PayKit\Exceptions;

use Solivellaluisaberto\PayKit\Enums\PaymentProvider;

/**
 * Excepción lanzada cuando hay errores en la comunicación con el proveedor de pagos
 */
class PaymentProviderException extends PaymentException
{
    /**
     * Error de API del proveedor
     */
    public static function apiError(
        PaymentProvider $provider,
        string $message,
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ): self {
        return new self(
            message: "{$provider->label()} API Error: {$message}",
            code: 2001,
            previous: $previous,
            context: [
                'provider' => $provider->value,
                'provider_error_code' => $errorCode,
            ]
        );
    }

    /**
     * Error de conexión con el proveedor
     */
    public static function connectionError(PaymentProvider $provider, ?\Throwable $previous = null): self
    {
        return new self(
            message: "Failed to connect to {$provider->label()} payment gateway. Please try again later.",
            code: 2002,
            previous: $previous,
            context: [
                'provider' => $provider->value,
            ]
        );
    }

    /**
     * Timeout al comunicarse con el proveedor
     */
    public static function timeout(PaymentProvider $provider): self
    {
        return new self(
            message: "Request to {$provider->label()} timed out. Please try again.",
            code: 2003,
            context: [
                'provider' => $provider->value,
            ]
        );
    }

    /**
     * Respuesta inválida del proveedor
     */
    public static function invalidResponse(PaymentProvider $provider, string $reason = ''): self
    {
        $message = "Invalid response from {$provider->label()}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            message: $message,
            code: 2004,
            context: [
                'provider' => $provider->value,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Pago rechazado por el proveedor
     */
    public static function paymentDeclined(
        PaymentProvider $provider,
        string $reason,
        ?string $declineCode = null
    ): self {
        return new self(
            message: "Payment declined by {$provider->label()}: {$reason}",
            code: 2005,
            context: [
                'provider' => $provider->value,
                'reason' => $reason,
                'decline_code' => $declineCode,
            ]
        );
    }

    /**
     * Verificación de firma fallida (Redsys, webhooks)
     */
    public static function signatureVerificationFailed(PaymentProvider $provider): self
    {
        return new self(
            message: "Signature verification failed for {$provider->label()}. Possible security issue.",
            code: 2006,
            context: [
                'provider' => $provider->value,
            ]
        );
    }

    /**
     * Pago no encontrado en el proveedor
     */
    public static function paymentNotFound(PaymentProvider $provider, string $paymentId): self
    {
        return new self(
            message: "Payment '{$paymentId}' not found in {$provider->label()}.",
            code: 2007,
            context: [
                'provider' => $provider->value,
                'payment_id' => $paymentId,
            ]
        );
    }

    /**
     * Reembolso no disponible
     */
    public static function refundNotAvailable(PaymentProvider $provider, string $reason): self
    {
        return new self(
            message: "Refund not available for {$provider->label()}: {$reason}",
            code: 2008,
            context: [
                'provider' => $provider->value,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Operación no soportada por el proveedor
     */
    public static function operationNotSupported(PaymentProvider $provider, string $operation): self
    {
        return new self(
            message: "Operation '{$operation}' is not supported by {$provider->label()}.",
            code: 2009,
            context: [
                'provider' => $provider->value,
                'operation' => $operation,
            ]
        );
    }

    public function getHttpStatusCode(): int
    {
        return match ($this->code) {
            2005 => 402, // Payment Required
            2007 => 404, // Not Found
            default => 502, // Bad Gateway
        };
    }
}

