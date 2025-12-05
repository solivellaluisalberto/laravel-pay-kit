<?php

namespace Solivellaluisaberto\PayKit\Exceptions;

use Exception;

/**
 * Excepción base para todos los errores relacionados con pagos
 */
class PaymentException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Obtener el contexto adicional del error
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Establecer contexto adicional
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Convertir la excepción a un array para logging/respuestas
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }

    /**
     * Renderizar para respuesta HTTP
     */
    public function render()
    {
        return response()->json($this->toArray(), $this->getHttpStatusCode());
    }

    /**
     * Obtener el código de estado HTTP apropiado
     */
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}

