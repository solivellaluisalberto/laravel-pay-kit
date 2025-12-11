<?php

namespace Solivellaluisaberto\PayKit;

use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysBizumPaymentService;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;

/**
 * Gestor principal de gateways de pago
 *
 * Esta clase actúa como un factory y manager centralizado para todos los proveedores
 * de pago y sus métodos. Permite obtener instancias de gateways de forma unificada,
 * con soporte para drivers personalizados y cacheo de instancias.
 *
 * @package Solivellaluisaberto\PayKit
 * @author Solivellaluisaberto
 */
class PayKit
{
    /**
     * Cache de instancias de gateways ya creados
     *
     * La clave es una combinación de proveedor y método (ej: "redsys.redirect")
     * para evitar crear múltiples instancias del mismo gateway.
     *
     * @var array<string, PaymentGateway>
     */
    private array $gateways = [];

    /**
     * Drivers personalizados registrados por el usuario
     *
     * Permite extender el sistema con proveedores de pago personalizados
     * sin modificar el código del paquete.
     *
     * @var array<string, callable>
     */
    private array $customDrivers = [];

    /**
     * Mapeo de proveedores y métodos de pago a sus clases de servicio
     *
     * Define qué clases de servicio se utilizan para cada combinación
     * de proveedor y método de pago. Esta estructura permite agregar
     * nuevos métodos fácilmente sin modificar la lógica principal.
     *
     * Las claves son strings (valores del enum PaymentProvider) porque
     * PHP no permite usar enums directamente como claves de array.
     *
     * @var array<string, array<string, string>>
     */
    private array $providers = [
        PaymentProvider::REDSYS->value => [
            'redirect' => RedsysRedirectPaymentService::class,
            'bizum' => RedsysBizumPaymentService::class,
        ],
    ];


    /**
     * Registrar un driver personalizado para un proveedor de pago
     *
     * Permite extender el sistema con proveedores de pago personalizados
     * o sobrescribir la implementación de proveedores existentes.
     *
     * El nombre puede ser:
     * - Un proveedor simple: 'mercadopago' (usará el primer método disponible)
     * - Una combinación proveedor.método: 'redsys.redirect' (específico)
     *
     * @param string $name Nombre del driver. Puede ser 'provider' o 'provider.method'
     * @param callable $driver Closure que recibe la instancia de PayKit y retorna
     *                        una instancia de PaymentGateway
     *
     * @return void
     *
     * @example
     * PayKit::extend('mercadopago', function($payKit) {
     *     return new MercadoPagoService(config('payments.mercadopago.key'));
     * });
     */
    public function extend(string $name, callable $driver): void
    {
        $this->customDrivers[$name] = $driver;
    }

    /**
     * Obtener una instancia del gateway
     *
     * Este método es el punto de entrada principal para obtener gateways de pago.
     * Soporta dos formas de uso:
     * 1. Pasando directamente la clase del gateway (recomendado)
     * 2. Pasando provider + method (compatibilidad)
     *
     * Implementa un sistema de cacheo para evitar crear múltiples instancias
     * del mismo gateway, y soporta drivers personalizados con mayor prioridad.
     *
     * Cada servicio maneja su propia configuración en el constructor, simplificando
     * la lógica de PayKit a solo instanciar y cachear.
     *
     * @param PaymentProvider|string|class-string<PaymentGateway> $provider Proveedor de pago o clase del gateway.
     *                                                                      Puede ser:
     *                                                                      - Una clase concreta (ej: RedsysRedirectPaymentService::class) - RECOMENDADO
     *                                                                      - Un enum PaymentProvider
     *                                                                      - Un string con el nombre del proveedor
     * @param string|null $method Método de pago específico (ej: 'redirect', 'bizum').
     *                           Solo se usa si $provider no es una clase.
     *                           Si es null, se utilizará el primer método disponible
     *
     * @return PaymentGateway Instancia del gateway solicitado
     *
     * @throws PaymentConfigurationException Si el proveedor no está soportado,
     *                                       si el método no existe para el proveedor,
     *                                       si la clase del servicio no existe,
     *                                       o si el driver personalizado retorna
     *                                       un tipo inválido
     * @throws PaymentProviderException Si se intenta usar un proveedor que no
     *                                 soporta procesamiento de pagos online (ej: CASH)
     *
     * @example
     * // Forma recomendada: usando clase concreta (más type-safe e intuitivo)
     * $gateway = PayKit::driver(RedsysRedirectPaymentService::class);
     * $gateway = PayKit::driver(RedsysBizumPaymentService::class);
     *
     * // Forma tradicional: usando enum (compatibilidad)
     * $gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');
     *
     * // Usando string
     * $gateway = PayKit::driver('redsys', 'bizum');
     *
     * // Sin especificar método (usa el primero disponible)
     * $gateway = PayKit::driver(PaymentProvider::REDSYS);
     */
    public function driver(PaymentProvider|string $provider, ?string $method = null): PaymentGateway
    {
        // Si se pasa una clase concreta, instanciarla directamente
        if (is_string($provider) && class_exists($provider) && is_subclass_of($provider, PaymentGateway::class)) {
            return $this->createFromClass($provider);
        }

        // Flujo tradicional: provider + method → mapear a clase y luego instanciar
        $providerEnum = $this->resolveProvider($provider);
        $providerName = $providerEnum->value;
        
        // Validar proveedores especiales temprano
        $this->validateProviderSupport($providerEnum);

        $method = $this->resolveMethod($providerName, $method);
        $cacheKey = "{$providerName}.{$method}";

        // Verificar cache
        if (isset($this->gateways[$cacheKey])) {
            return $this->gateways[$cacheKey];
        }

        // Intentar resolver desde driver personalizado
        $gateway = $this->resolveCustomDriver($cacheKey);
        if ($gateway !== null) {
            return $this->cacheGateway($cacheKey, $gateway);
        }

        // Mapear provider + method a clase y crear instancia
        return $this->createFromProviderMethod($providerName, $method, $cacheKey);
    }

    /**
     * Resolver y validar el proveedor de pago
     *
     * Normaliza el proveedor a un enum PaymentProvider, validando que existe
     * en el sistema.
     *
     * @param PaymentProvider|string $provider Proveedor de pago (enum o string)
     *
     * @return PaymentProvider Enum del proveedor validado
     *
     * @throws PaymentConfigurationException Si el proveedor no es válido
     */
    private function resolveProvider(PaymentProvider|string $provider): PaymentProvider
    {
        if ($provider instanceof PaymentProvider) {
            return $provider;
        }

        try {
            return PaymentProvider::from($provider);
        } catch (\ValueError $e) {
            throw PaymentConfigurationException::unsupportedProvider($provider);
        }
    }

    /**
     * Validar que el proveedor soporta procesamiento de pagos online
     *
     * Algunos proveedores como CASH no soportan pagos online y deben
     * rechazarse temprano en el flujo.
     *
     * @param PaymentProvider $provider Proveedor a validar
     *
     * @return void
     *
     * @throws PaymentProviderException Si el proveedor no soporta pagos online
     */
    private function validateProviderSupport(PaymentProvider $provider): void
    {
        if ($provider === PaymentProvider::CASH) {
            throw PaymentProviderException::operationNotSupported(
                PaymentProvider::CASH,
                'online payment processing'
            );
        }
    }

    /**
     * Resolver el método de pago para un proveedor
     *
     * Si el método es null, utiliza el primer método disponible para el proveedor.
     * Valida que el proveedor tenga métodos disponibles.
     *
     * @param string $providerName Nombre del proveedor (valor del enum)
     * @param string|null $method Método específico o null para usar el primero
     *
     * @return string Método de pago resuelto
     *
     * @throws PaymentConfigurationException Si el proveedor no tiene métodos disponibles
     */
    private function resolveMethod(string $providerName, ?string $method): string
    {
        if ($method !== null) {
            return $method;
        }

        // Validar que el proveedor existe y tiene métodos
        if (!isset($this->providers[$providerName]) || empty($this->providers[$providerName])) {
            throw PaymentConfigurationException::unsupportedProvider($providerName);
        }

        // Usar el primer método disponible
        return array_key_first($this->providers[$providerName]);
    }

    /**
     * Resolver gateway desde driver personalizado
     *
     * Busca si existe un driver personalizado registrado para la clave
     * de cache especificada y lo instancia.
     *
     * @param string $cacheKey Clave de cache (formato: "provider.method")
     *
     * @return PaymentGateway|null Instancia del gateway o null si no existe driver personalizado
     *
     * @throws PaymentConfigurationException Si el driver personalizado retorna un tipo inválido
     */
    private function resolveCustomDriver(string $cacheKey): ?PaymentGateway
    {
        if (!isset($this->customDrivers[$cacheKey])) {
            return null;
        }

        $gateway = $this->customDrivers[$cacheKey]($this);

        if (! $gateway instanceof PaymentGateway) {
            throw PaymentConfigurationException::invalidConfiguration(
                $cacheKey,
                "Custom driver must return an instance of PaymentGateway"
            );
        }

        return $gateway;
    }

    /**
     * Crear gateway directamente desde una clase concreta
     *
     * Cada servicio maneja su propia configuración en el constructor,
     * por lo que solo necesitamos instanciarlo.
     *
     * @param class-string<PaymentGateway> $gatewayClass Clase del gateway a instanciar
     *
     * @return PaymentGateway Instancia del gateway
     *
     * @throws PaymentConfigurationException Si la clase no existe o no implementa PaymentGateway
     */
    private function createFromClass(string $gatewayClass): PaymentGateway
    {
        // Validar que la clase existe y es un PaymentGateway
        if (!class_exists($gatewayClass)) {
            throw PaymentConfigurationException::invalidConfiguration(
                $gatewayClass,
                "Gateway class '{$gatewayClass}' does not exist"
            );
        }

        if (!is_subclass_of($gatewayClass, PaymentGateway::class)) {
            throw PaymentConfigurationException::invalidConfiguration(
                $gatewayClass,
                "Class '{$gatewayClass}' must implement PaymentGateway interface"
            );
        }

        // Usar la clase como clave de cache
        $cacheKey = $gatewayClass;

        // Verificar cache
        if (isset($this->gateways[$cacheKey])) {
            return $this->gateways[$cacheKey];
        }

        // Intentar resolver desde driver personalizado
        $gateway = $this->resolveCustomDriver($cacheKey);
        if ($gateway !== null) {
            return $this->cacheGateway($cacheKey, $gateway);
        }

        // Instanciar el gateway (cada clase maneja su propia configuración)
        $gateway = new $gatewayClass();

        if (! $gateway instanceof PaymentGateway) {
            throw PaymentConfigurationException::invalidConfiguration(
                $gatewayClass,
                "Class '{$gatewayClass}' must return an instance of PaymentGateway"
            );
        }

        return $this->cacheGateway($cacheKey, $gateway);
    }

    /**
     * Crear gateway desde provider + method (mapear a clase y luego instanciar)
     *
     * @param string $providerName Nombre del proveedor
     * @param string $method Método de pago
     * @param string $cacheKey Clave de cache
     *
     * @return PaymentGateway Instancia del gateway creado
     *
     * @throws PaymentConfigurationException Si la configuración es inválida
     */
    private function createFromProviderMethod(
        string $providerName,
        string $method,
        string $cacheKey
    ): PaymentGateway {
        // Validar que el proveedor y método existen en la configuración
        if (!isset($this->providers[$providerName][$method])) {
            throw PaymentConfigurationException::unsupportedProvider(
                "{$providerName}.{$method}"
            );
        }

        // Obtener la clase del servicio
        $serviceClass = $this->providers[$providerName][$method];

        // Validar que la clase existe
        if (!class_exists($serviceClass)) {
            throw PaymentConfigurationException::unsupportedProvider(
                "{$providerName}.{$method}"
            );
        }

        // Crear instancia (cada clase maneja su propia configuración)
        return $this->createFromClass($serviceClass);
    }


    /**
     * Cachear una instancia de gateway
     *
     * Almacena el gateway en el cache interno para futuras solicitudes.
     *
     * @param string $cacheKey Clave de cache
     * @param PaymentGateway $gateway Instancia del gateway a cachear
     *
     * @return PaymentGateway La misma instancia (para chaining)
     */
    private function cacheGateway(string $cacheKey, PaymentGateway $gateway): PaymentGateway
    {
        $this->gateways[$cacheKey] = $gateway;
        return $gateway;
    }

    
}
