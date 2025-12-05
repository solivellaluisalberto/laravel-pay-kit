<?php

namespace Solivellaluisaberto\PayKit\Exceptions;

use Solivellaluisaberto\PayKit\Enums\PaymentState;

/**
 * Excepción lanzada cuando se intenta realizar una operación en un estado de pago inválido
 */
class InvalidPaymentStateException extends PaymentException
{
    /**
     * No se puede capturar el pago en su estado actual
     */
    public static function cannotCapture(string $paymentId, string $currentState): self
    {
        return new self(
            message: "Cannot capture payment '{$paymentId}' in state '{$currentState}'. Payment must be in 'pending' or 'authorized' state.",
            code: 4001,
            context: [
                'payment_id' => $paymentId,
                'current_state' => $currentState,
            ]
        );
    }

    /**
     * No se puede reembolsar el pago en su estado actual
     */
    public static function cannotRefund(string $paymentId, string $currentState): self
    {
        return new self(
            message: "Cannot refund payment '{$paymentId}' in state '{$currentState}'. Payment must be in 'completed' state.",
            code: 4002,
            context: [
                'payment_id' => $paymentId,
                'current_state' => $currentState,
            ]
        );
    }

    /**
     * No se puede cancelar el pago en su estado actual
     */
    public static function cannotCancel(string $paymentId, string $currentState): self
    {
        return new self(
            message: "Cannot cancel payment '{$paymentId}' in state '{$currentState}'. Payment can only be cancelled when pending.",
            code: 4003,
            context: [
                'payment_id' => $paymentId,
                'current_state' => $currentState,
            ]
        );
    }

    /**
     * Pago ya procesado
     */
    public static function alreadyProcessed(string $paymentId): self
    {
        return new self(
            message: "Payment '{$paymentId}' has already been processed.",
            code: 4004,
            context: [
                'payment_id' => $paymentId,
            ]
        );
    }

    /**
     * Pago expirado
     */
    public static function expired(string $paymentId): self
    {
        return new self(
            message: "Payment '{$paymentId}' has expired.",
            code: 4005,
            context: [
                'payment_id' => $paymentId,
            ]
        );
    }

    /**
     * Transición de estado inválida
     */
    public static function invalidStateTransition(
        string $paymentId,
        string $fromState,
        string $toState
    ): self {
        return new self(
            message: "Invalid state transition for payment '{$paymentId}': cannot transition from '{$fromState}' to '{$toState}'.",
            code: 4006,
            context: [
                'payment_id' => $paymentId,
                'from_state' => $fromState,
                'to_state' => $toState,
            ]
        );
    }

    /**
     * Pago ya reembolsado
     */
    public static function alreadyRefunded(string $paymentId): self
    {
        return new self(
            message: "Payment '{$paymentId}' has already been refunded.",
            code: 4007,
            context: [
                'payment_id' => $paymentId,
            ]
        );
    }

    /**
     * Monto de reembolso inválido
     */
    public static function invalidRefundAmount(
        string $paymentId,
        float $requestedAmount,
        float $availableAmount
    ): self {
        return new self(
            message: "Cannot refund {$requestedAmount} for payment '{$paymentId}'. Maximum refundable amount is {$availableAmount}.",
            code: 4008,
            context: [
                'payment_id' => $paymentId,
                'requested_amount' => $requestedAmount,
                'available_amount' => $availableAmount,
            ]
        );
    }

    public function getHttpStatusCode(): int
    {
        return 409; // Conflict
    }
}

