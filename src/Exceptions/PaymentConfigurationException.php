<?php

namespace Solivellaluisaberto\PayKit\Exceptions;

/**
 * Excepción lanzada cuando falta configuración necesaria para procesar pagos
 */
class PaymentConfigurationException extends PaymentException
{
    /**
     * Credenciales faltantes
     */
    public static function missingCredentials(string $provider, string $credential): self
    {
        return new self(
            message: "Missing {$credential} for {$provider}. Please configure it in your .env file or config/payments.php",
            code: 1001,
            context: [
                'provider' => $provider,
                'credential' => $credential,
                'config_key' => "payments.{$provider}.{$credential}",
            ]
        );
    }

    /**
     * Clave API inválida
     */
    public static function invalidApiKey(string $provider): self
    {
        return new self(
            message: "Invalid API key for {$provider}. Please check your credentials.",
            code: 1002,
            context: [
                'provider' => $provider,
            ]
        );
    }

    /**
     * Entorno no válido
     */
    public static function invalidEnvironment(string $provider, string $environment): self
    {
        return new self(
            message: "Invalid environment '{$environment}' for {$provider}. Expected 'test', 'sandbox' or 'live'.",
            code: 1003,
            context: [
                'provider' => $provider,
                'environment' => $environment,
            ]
        );
    }

    /**
     * Proveedor no soportado
     */
    public static function unsupportedProvider(string $provider): self
    {
        return new self(
            message: "Payment provider '{$provider}' is not supported.",
            code: 1004,
            context: [
                'provider' => $provider,
            ]
        );
    }

    /**
     * Configuración general inválida
     */
    public static function invalidConfiguration(string $provider, string $reason): self
    {
        return new self(
            message: "Invalid configuration for {$provider}: {$reason}",
            code: 1005,
            context: [
                'provider' => $provider,
                'reason' => $reason,
            ]
        );
    }

    public function getHttpStatusCode(): int
    {
        return 500; // Internal Server Error
    }
}

