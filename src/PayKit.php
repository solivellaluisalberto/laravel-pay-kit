<?php

namespace Solivellaluisaberto\PayKit;

use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysBizumPaymentService;
use Solivellaluisaberto\PayKit\Services\Redsys\RedsysCardPaymentService;

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
     * La clave es una combinación de proveedor y método (ej: "redsys.card")
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
            'card' => RedsysCardPaymentService::class,
            'bizum' => RedsysBizumPaymentService::class,
        ],
    ];

    /**
     * Mapeo de proveedores a sus métodos factory correspondientes
     *
     * Este array permite asociar cada proveedor con su método factory
     * específico. Facilita la escalabilidad al permitir agregar nuevos
     * proveedores simplemente añadiendo una entrada aquí y creando
     * el método factory correspondiente.
     *
     * La clave es el valor del enum PaymentProvider (string) y el valor
     * es el nombre del método factory privado que debe ser llamado.
     *
     * @var array<string, string>
     */
    private array $factories = [
        PaymentProvider::REDSYS->value => 'createRedsysService',
    ];

    /**
     * Registrar un driver personalizado para un proveedor de pago
     *
     * Permite extender el sistema con proveedores de pago personalizados
     * o sobrescribir la implementación de proveedores existentes.
     *
     * El nombre puede ser:
     * - Un proveedor simple: 'mercadopago' (usará el primer método disponible)
     * - Una combinación proveedor.método: 'redsys.card' (específico)
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
     * Obtener una instancia del gateway para un proveedor y método específicos
     *
     * Este método es el punto de entrada principal para obtener gateways de pago.
     * Implementa un sistema de cacheo para evitar crear múltiples instancias
     * del mismo gateway, y soporta drivers personalizados con mayor prioridad.
     *
     * Flujo de resolución:
     * 1. Normaliza y valida el proveedor
     * 2. Resuelve el método si no se especifica
     * 3. Verifica si el gateway ya está en cache
     * 4. Busca drivers personalizados registrados
     * 5. Busca en la configuración de proveedores del paquete
     * 6. Instancia el servicio usando el factory correspondiente
     * 7. Cachea la instancia para futuras solicitudes
     *
     * @param PaymentProvider|string $provider Proveedor de pago. Puede ser un enum
     *                                         PaymentProvider o un string con el nombre
     * @param string|null $method Método de pago específico (ej: 'card', 'bizum').
     *                           Si es null, se utilizará el primer método disponible
     *                           para el proveedor especificado
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
     * // Usando enum
     * $gateway = PayKit::driver(PaymentProvider::REDSYS, 'card');
     *
     * // Usando string
     * $gateway = PayKit::driver('redsys', 'bizum');
     *
     * // Sin especificar método (usa el primero disponible)
     * $gateway = PayKit::driver(PaymentProvider::REDSYS);
     */
    public function driver(PaymentProvider|string $provider, ?string $method = null): PaymentGateway
    {
        $providerEnum = $this->resolveProvider($provider);
        $providerName = $providerEnum->value;
        
        // Validar proveedores especiales temprano (antes de cualquier procesamiento)
        $this->validateProviderSupport($providerEnum);

        $method = $this->resolveMethod($providerName, $method);
        $cacheKey = "{$providerName}.{$method}";

        // Verificar cache antes de cualquier procesamiento
        if (isset($this->gateways[$cacheKey])) {
            return $this->gateways[$cacheKey];
        }

        // Intentar resolver desde driver personalizado
        $gateway = $this->resolveCustomDriver($cacheKey);
        if ($gateway !== null) {
            return $this->cacheGateway($cacheKey, $gateway);
        }

        // Resolver desde configuración del paquete
        return $this->createFromPackageConfig($providerEnum, $providerName, $method, $cacheKey);
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
     * Crear gateway desde la configuración del paquete
     *
     * Crea una instancia del gateway usando la configuración interna
     * del paquete, validando todas las dependencias necesarias.
     *
     * @param PaymentProvider $providerEnum Enum del proveedor
     * @param string $providerName Nombre del proveedor (valor del enum)
     * @param string $method Método de pago
     * @param string $cacheKey Clave de cache
     *
     * @return PaymentGateway Instancia del gateway creado
     *
     * @throws PaymentConfigurationException Si la configuración es inválida
     */
    private function createFromPackageConfig(
        PaymentProvider $providerEnum,
        string $providerName,
        string $method,
        string $cacheKey
    ): PaymentGateway {
        // Validar que el proveedor y método existen en la configuración
        $this->validateProviderMethod($providerName, $method);

        // Validar que el factory existe antes de continuar
        $this->validateFactoryExists($providerName);

        // Obtener la clase del servicio
        $serviceClass = $this->providers[$providerName][$method];

        // Validar que la clase existe
        if (!class_exists($serviceClass)) {
            throw PaymentConfigurationException::unsupportedProvider(
                "{$providerName}.{$method}"
            );
        }

        // Crear instancia usando el factory
        $gateway = $this->createFromFactory($providerName, $serviceClass);

        // Validar que el factory retornó una instancia válida
        if (! $gateway instanceof PaymentGateway) {
            $factoryMethod = $this->factories[$providerName];
            throw PaymentConfigurationException::invalidConfiguration(
                $cacheKey,
                "Factory method '{$factoryMethod}' must return an instance of PaymentGateway"
            );
        }

        return $this->cacheGateway($cacheKey, $gateway);
    }

    /**
     * Validar que el proveedor y método existen en la configuración
     *
     * @param string $providerName Nombre del proveedor
     * @param string $method Método de pago
     *
     * @return void
     *
     * @throws PaymentConfigurationException Si el proveedor o método no existen
     */
    private function validateProviderMethod(string $providerName, string $method): void
    {
        if (!isset($this->providers[$providerName][$method])) {
            throw PaymentConfigurationException::unsupportedProvider(
                "{$providerName}.{$method}"
            );
        }
    }

    /**
     * Validar que existe un factory para el proveedor
     *
     * @param string $providerName Nombre del proveedor
     *
     * @return void
     *
     * @throws PaymentConfigurationException Si no existe factory para el proveedor
     */
    private function validateFactoryExists(string $providerName): void
    {
        if (!isset($this->factories[$providerName])) {
            throw PaymentConfigurationException::unsupportedProvider($providerName);
        }

        $factoryMethod = $this->factories[$providerName];

        if (!method_exists($this, $factoryMethod) || !is_callable([$this, $factoryMethod])) {
            throw PaymentConfigurationException::invalidConfiguration(
                $providerName,
                "Factory method '{$factoryMethod}' does not exist or is not callable"
            );
        }
    }

    /**
     * Crear gateway usando el factory correspondiente
     *
     * @param string $providerName Nombre del proveedor
     * @param string $serviceClass Clase del servicio a instanciar
     *
     * @return PaymentGateway Instancia del gateway creado
     */
    private function createFromFactory(string $providerName, string $serviceClass): PaymentGateway
    {
        $factoryMethod = $this->factories[$providerName];
        return $this->{$factoryMethod}($serviceClass);
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

    /**
     * Factory method para crear instancias de servicios de Redsys
     *
     * Este método se encarga de instanciar cualquier servicio de Redsys
     * (RedsysCardPaymentService, RedsysBizumPaymentService, etc.) pasando
     * la configuración necesaria desde el archivo de configuración.
     *
     * La configuración se lee de 'pay-kit.redsys' y se cachea estáticamente
     * a nivel de método para evitar múltiples llamadas a config() durante
     * la misma ejecución del script. Esto es seguro porque la configuración
     * de Laravel es inmutable durante la ejecución de una request.
     *
     * Nota: El cacheo estático aquí es complementario al cacheo de instancias
     * en la propiedad $gateways. Este cachea la lectura de config(), mientras
     * que $gateways cachea las instancias completas de los servicios.
     *
     * @param string $serviceClass Nombre completo de la clase del servicio
     *                            a instanciar (debe extender RedsysPaymentService)
     *
     * @return PaymentGateway Instancia del servicio de Redsys configurado
     *
     * @throws PaymentConfigurationException Si la clase no existe o no puede
     *                                       ser instanciada
     *
     * @internal Este método es llamado internamente por driver() cuando
     *          se solicita un gateway de Redsys. No debe ser llamado
     *          directamente desde código externo.
     */
    private function createRedsysService(string $serviceClass): PaymentGateway
    {
        // Cachear la configuración estáticamente para evitar múltiples llamadas a config()
        // durante la misma ejecución del script. Esto es seguro porque la configuración
        // de Laravel normalmente no cambia durante la ejecución de una request.
        static $config = null;
        
        if ($config === null) {
            $config = config('pay-kit.redsys', []);
        }

        return new $serviceClass(
            merchantCode: $config['merchant_code'] ?? null,
            secretKey: $config['secret_key'] ?? null,
            terminal: $config['terminal'] ?? null,
            environment: $config['environment'] ?? null
        );
    }
    
}
