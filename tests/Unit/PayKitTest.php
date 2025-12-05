<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit;

use Mockery;
use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\PayKit;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PayKitTest extends TestCase
{
    protected PayKit $payKit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payKit = new PayKit();

        // Configurar Redsys para los tests
        config([
            'pay-kit.redsys' => [
                'merchant_code' => '999999999',
                'secret_key' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
                'terminal' => '1',
                'environment' => 'test',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $payKit = new PayKit();

        $this->assertInstanceOf(PayKit::class, $payKit);
    }

    /** @test */
    public function it_can_register_custom_driver(): void
    {
        $mockGateway = Mockery::mock(PaymentGateway::class);

        $this->payKit->extend('custom.provider', function ($payKit) use ($mockGateway) {
            return $mockGateway;
        });

        // Verificar que el driver fue registrado usando reflection
        $reflection = new \ReflectionClass($this->payKit);
        $property = $reflection->getProperty('customDrivers');
        $customDrivers = $property->getValue($this->payKit);

        $this->assertArrayHasKey('custom.provider', $customDrivers);
        $this->assertIsCallable($customDrivers['custom.provider']);
    }

    /** @test */
    public function it_can_get_driver_with_enum_provider_and_method(): void
    {
        $gateway = $this->payKit->driver(PaymentProvider::REDSYS, 'card');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
    }

    /** @test */
    public function it_can_get_driver_with_string_provider_and_method(): void
    {
        $gateway = $this->payKit->driver('redsys', 'card');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
    }

    /** @test */
    public function it_uses_first_available_method_when_method_is_null(): void
    {
        $gateway = $this->payKit->driver(PaymentProvider::REDSYS);

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
    }

    /** @test */
    public function it_caches_gateway_instances(): void
    {
        $gateway1 = $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        $gateway2 = $this->payKit->driver(PaymentProvider::REDSYS, 'card');

        // Debe ser la misma instancia (cacheada)
        $this->assertSame($gateway1, $gateway2);
    }

    /** @test */
    public function it_creates_different_instances_for_different_methods(): void
    {
        $cardGateway = $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        $bizumGateway = $this->payKit->driver(PaymentProvider::REDSYS, 'bizum');

        // Deben ser instancias diferentes
        $this->assertNotSame($cardGateway, $bizumGateway);
        $this->assertInstanceOf(PaymentGateway::class, $cardGateway);
        $this->assertInstanceOf(PaymentGateway::class, $bizumGateway);
    }

    /** @test */
    public function it_uses_custom_driver_when_registered(): void
    {
        $mockGateway = Mockery::mock(PaymentGateway::class);

        $this->payKit->extend('redsys.card', function ($payKit) use ($mockGateway) {
            return $mockGateway;
        });

        $gateway = $this->payKit->driver(PaymentProvider::REDSYS, 'card');

        // Debe ser el mock, no la instancia real
        $this->assertSame($mockGateway, $gateway);
    }

    /** @test */
    public function it_uses_custom_driver_for_provider_only(): void
    {
        $mockGateway = Mockery::mock(PaymentGateway::class);

        $this->payKit->extend('redsys', function ($payKit) use ($mockGateway) {
            return $mockGateway;
        });

        $gateway = $this->payKit->driver(PaymentProvider::REDSYS);

        // Debe usar el primer método disponible, pero con el driver personalizado
        // En este caso, el driver personalizado solo cubre 'redsys', no 'redsys.card'
        // Así que debería usar la implementación normal
        $this->assertInstanceOf(PaymentGateway::class, $gateway);
    }

    /** @test */
    public function it_throws_exception_when_provider_is_invalid_string(): void
    {
        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Payment provider 'invalid_provider' is not supported.");

        $this->payKit->driver('invalid_provider', 'card');
    }

    /** @test */
    public function it_throws_exception_when_method_not_supported(): void
    {
        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Payment provider 'redsys.invalid_method' is not supported.");

        $this->payKit->driver(PaymentProvider::REDSYS, 'invalid_method');
    }

    /** @test */
    public function it_throws_exception_when_provider_not_in_providers_array(): void
    {
        // Usar reflection para modificar el array de providers y quitar redsys
        $reflection = new \ReflectionClass($this->payKit);
        $property = $reflection->getProperty('providers');
        
        // Guardar el valor original
        $originalProviders = $property->getValue($this->payKit);
        
        // Temporalmente quitar redsys
        $property->setValue($this->payKit, []);

        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Payment provider 'redsys' is not supported.");

        try {
            $this->payKit->driver(PaymentProvider::REDSYS);
        } finally {
            // Restaurar el valor original
            $property->setValue($this->payKit, $originalProviders);
        }
    }

    /** @test */
    public function it_throws_exception_when_custom_driver_returns_invalid_type(): void
    {
        $this->payKit->extend('redsys.card', function ($payKit) {
            return 'not-a-gateway';
        });

        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Custom driver must return an instance of PaymentGateway");

        $this->payKit->driver(PaymentProvider::REDSYS, 'card');
    }

    /** @test */
    public function it_throws_exception_for_cash_provider_online_payment(): void
    {
        // Para testear la validación de CASH, necesitamos agregarlo temporalmente
        // a los arrays de providers y factories usando una clase válida
        $reflection = new \ReflectionClass($this->payKit);
        $providersProperty = $reflection->getProperty('providers');
        $factoriesProperty = $reflection->getProperty('factories');
        
        $originalProviders = $providersProperty->getValue($this->payKit);
        $originalFactories = $factoriesProperty->getValue($this->payKit);
        
        // Agregar CASH usando una clase de servicio existente de Redsys
        // para que pase la validación de class_exists, pero luego falle en la validación de CASH
        $providersProperty->setValue($this->payKit, array_merge($originalProviders, [
            PaymentProvider::CASH->value => ['default' => \Solivellaluisaberto\PayKit\Services\Redsys\RedsysCardPaymentService::class],
        ]));
        $factoriesProperty->setValue($this->payKit, array_merge($originalFactories, [
            PaymentProvider::CASH->value => 'createRedsysService',
        ]));

        $this->expectException(PaymentProviderException::class);
        $this->expectExceptionMessage("Operation 'online payment processing' is not supported by Commerce.");

        try {
            $this->payKit->driver(PaymentProvider::CASH, 'default');
        } finally {
            // Restaurar valores originales
            $providersProperty->setValue($this->payKit, $originalProviders);
            $factoriesProperty->setValue($this->payKit, $originalFactories);
        }
    }

    /** @test */
    public function it_throws_exception_when_factory_method_not_found(): void
    {
        $reflection = new \ReflectionClass($this->payKit);
        $factoriesProperty = $reflection->getProperty('factories');
        
        $originalFactories = $factoriesProperty->getValue($this->payKit);
        
        // Temporalmente usar un método que no existe
        $factoriesProperty->setValue($this->payKit, [
            PaymentProvider::REDSYS->value => 'nonExistentFactoryMethod',
        ]);

        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Factory method 'nonExistentFactoryMethod' does not exist or is not callable");

        try {
            $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        } finally {
            // Restaurar valor original
            $factoriesProperty->setValue($this->payKit, $originalFactories);
        }
    }

    /** @test */
    public function it_throws_exception_when_service_class_does_not_exist(): void
    {
        $reflection = new \ReflectionClass($this->payKit);
        $providersProperty = $reflection->getProperty('providers');
        
        $originalProviders = $providersProperty->getValue($this->payKit);
        
        // Temporalmente usar una clase que no existe
        $providersProperty->setValue($this->payKit, [
            PaymentProvider::REDSYS->value => [
                'card' => 'NonExistentServiceClass',
            ],
        ]);

        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Payment provider 'redsys.card' is not supported.");

        try {
            $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        } finally {
            // Restaurar valor original
            $providersProperty->setValue($this->payKit, $originalProviders);
        }
    }

    /** @test */
    public function it_can_get_different_gateways_for_same_provider_different_methods(): void
    {
        $cardGateway = $this->payKit->driver('redsys', 'card');
        $bizumGateway = $this->payKit->driver('redsys', 'bizum');

        $this->assertInstanceOf(PaymentGateway::class, $cardGateway);
        $this->assertInstanceOf(PaymentGateway::class, $bizumGateway);
        $this->assertNotSame($cardGateway, $bizumGateway);
    }

    /** @test */
    public function it_handles_provider_with_empty_methods(): void
    {
        $reflection = new \ReflectionClass($this->payKit);
        $providersProperty = $reflection->getProperty('providers');
        
        $originalProviders = $providersProperty->getValue($this->payKit);
        
        // Temporalmente crear un proveedor sin métodos
        $providersProperty->setValue($this->payKit, [
            PaymentProvider::REDSYS->value => [],
        ]);

        $this->expectException(PaymentConfigurationException::class);
        $this->expectExceptionMessage("Payment provider 'redsys' is not supported.");

        try {
            $this->payKit->driver(PaymentProvider::REDSYS);
        } finally {
            // Restaurar valor original
            $providersProperty->setValue($this->payKit, $originalProviders);
        }
    }

    /** @test */
    public function it_caches_custom_driver_instances(): void
    {
        $callCount = 0;
        $mockGateway = Mockery::mock(PaymentGateway::class);

        // Usar un proveedor válido del enum y un método personalizado
        $this->payKit->extend('redsys.custom', function ($payKit) use ($mockGateway, &$callCount) {
            $callCount++;
            return $mockGateway;
        });

        $gateway1 = $this->payKit->driver(PaymentProvider::REDSYS, 'custom');
        $gateway2 = $this->payKit->driver(PaymentProvider::REDSYS, 'custom');

        // Debe ser la misma instancia
        $this->assertSame($gateway1, $gateway2);
        // El driver personalizado solo debe haberse llamado una vez debido al cache
        // Nota: En realidad, el driver se llama una vez y luego se cachea
        $this->assertEquals(1, $callCount);
    }

    /** @test */
    public function it_can_extend_multiple_custom_drivers(): void
    {
        $mockGateway1 = Mockery::mock(PaymentGateway::class);
        $mockGateway2 = Mockery::mock(PaymentGateway::class);

        $this->payKit->extend('custom1', function ($payKit) use ($mockGateway1) {
            return $mockGateway1;
        });

        $this->payKit->extend('custom2', function ($payKit) use ($mockGateway2) {
            return $mockGateway2;
        });

        // Verificar que ambos drivers fueron registrados
        $reflection = new \ReflectionClass($this->payKit);
        $property = $reflection->getProperty('customDrivers');
        $customDrivers = $property->getValue($this->payKit);

        $this->assertArrayHasKey('custom1', $customDrivers);
        $this->assertArrayHasKey('custom2', $customDrivers);
    }

    /** @test */
    public function it_normalizes_enum_provider_to_string(): void
    {
        // Obtener gateway con enum
        $gateway1 = $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        
        // Obtener gateway con string (debe ser la misma instancia cacheada)
        $gateway2 = $this->payKit->driver('redsys', 'card');

        $this->assertSame($gateway1, $gateway2);
    }

    /** @test */
    public function it_uses_first_method_when_multiple_methods_available(): void
    {
        // Cuando no se especifica método, debe usar el primero disponible
        // En el caso de Redsys, el primer método debería ser 'card' (según el orden del array)
        $gateway = $this->payKit->driver(PaymentProvider::REDSYS);
        
        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        
        // Verificar que es la misma instancia que si pedimos 'card' específicamente
        $cardGateway = $this->payKit->driver(PaymentProvider::REDSYS, 'card');
        $this->assertSame($gateway, $cardGateway);
    }
}
