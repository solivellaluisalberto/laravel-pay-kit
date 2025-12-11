# Laravel Pay Kit

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.0%2B%20%7C%2011.0%2B%20%7C%2012.0%2B-red.svg)](https://laravel.com)
[![Tests](https://img.shields.io/badge/tests-124%20passing-brightgreen.svg)](https://github.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Un paquete profesional y completo para Laravel que simplifica la integración de múltiples proveedores de pago. Diseñado con una arquitectura limpia, extensible y type-safe, permite gestionar pagos de forma unificada con soporte para múltiples gateways.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso Rápido](#-uso-rápido)
- [Documentación Completa](#-documentación-completa)
- [Servicios de Pago](#-servicios-de-pago)
- [Ejemplos Avanzados](#-ejemplos-avanzados)
- [Extensibilidad](#-extensibilidad)
- [Testing](#-testing)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

## ✨ Características

- 🎯 **Arquitectura Limpia**: Diseño basado en contratos e interfaces, fácil de extender
- 🔒 **Type-Safe**: Uso extensivo de enums y type hints para mayor seguridad
- 🚀 **Múltiples Proveedores**: Soporte para Redsys (tarjeta y Bizum), con arquitectura preparada para más
- 💳 **Múltiples Monedas**: Soporte para 40+ monedas con códigos ISO 4217
- 🔄 **Cache Inteligente**: Sistema de cacheo automático de instancias de gateways
- 🛡️ **Validación Robusta**: Validación exhaustiva de datos de entrada
- 📝 **Logging Integrado**: Sistema de logging completo para auditoría
- 🧪 **100% Testeado**: 124 tests con 359 assertions, cobertura completa
- 🔌 **Extensible**: Sistema de drivers personalizados para agregar nuevos proveedores
- 📦 **Laravel Nativo**: Integración perfecta con el ecosistema Laravel

## 📦 Requisitos

- PHP 8.1 o superior
- Laravel 10.0, 11.0 o 12.0
- Composer

## 🚀 Instalación

### Paso 1: Instalar el paquete

```bash
composer require solivellaluisaberto/laravel-pay-kit
```

### Paso 2: Publicar la configuración

```bash
php artisan vendor:publish --provider="Solivellaluisaberto\PayKit\PayKitServiceProvider" --tag="config"
```

Esto creará el archivo `config/pay-kit.php` en tu proyecto.

### Paso 3: Configurar variables de entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# Moneda por defecto
PAY_KIT_CURRENCY=EUR

# Logging
PAY_KIT_LOGGING_ENABLED=false
PAY_KIT_LOGGING_CHANNEL=payments

# Configuración de Redsys
REDSYS_MERCHANT_CODE=tu_codigo_comercio
REDSYS_SECRET_KEY=tu_clave_secreta
REDSYS_TERMINAL=1
REDSYS_ENVIRONMENT=test
```

## ⚙️ Configuración

El archivo de configuración `config/pay-kit.php` contiene todas las opciones del paquete:

```php
return [
    // Moneda por defecto para los pagos
    'currency' => env('PAY_KIT_CURRENCY', 'EUR'),

    // Configuración de logging
    'logging' => [
        'enabled' => env('PAY_KIT_LOGGING_ENABLED', false),
        'channel' => env('PAY_KIT_LOGGING_CHANNEL', 'payments'),
    ],

    // Configuración de Redsys
    'redsys' => [
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'secret_key' => env('REDSYS_SECRET_KEY'),
        'terminal' => env('REDSYS_TERMINAL', '1'),
        'environment' => env('REDSYS_ENVIRONMENT', 'test'), // 'test' o 'live'
    ],
];
```

## 🎯 Uso Rápido

### Ejemplo Básico: Iniciar un Pago

```php
use Solivellaluisaberto\PayKit\Facades\PayKit;
use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Enums\Currency;
use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;

// Crear solicitud de pago
$request = new PaymentRequestData(
    amount: 99.99,
    currency: Currency::EUR,
    orderId: 'ORD-12345',
    returnUrl: route('payment.return'),
    cancelUrl: route('payment.cancel'),
    notificationUrl: route('payment.notification'),
    metadata: [
        'description' => 'Compra de productos',
        'customer_email' => 'cliente@example.com'
    ]
);

// Obtener gateway (forma recomendada: usando clase concreta)
$gateway = PayKit::driver(RedsysRedirectPaymentService::class);

// O forma tradicional: usando provider + method
$gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');

// Iniciar el pago
$response = $gateway->initiate($request);

// Renderizar el formulario HTML en tu vista
return view('payment.form', ['formHtml' => $response->formHtml]);
```

### Ejemplo: Procesar Callback de Redsys

```php
use Solivellaluisaberto\PayKit\Facades\PayKit;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;

Route::post('/payment/redsys/callback', function (Request $request) {
    $gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');
    
    try {
        $result = $gateway->verifyCallback($request->all());
        
        if ($result->success && $result->isCompleted()) {
            // Pago exitoso
            Order::where('order_id', $result->paymentId)
                ->update([
                    'status' => 'paid',
                    'payment_status' => 'completed',
                    'transaction_id' => $result->transactionId
                ]);
            
            return response()->json(['status' => 'success']);
        } else {
            // Pago fallido
            Log::error('Payment failed', ['result' => $result]);
            return response()->json(['status' => 'failed'], 400);
        }
    } catch (\Exception $e) {
        Log::error('Payment callback error', ['error' => $e->getMessage()]);
        return response()->json(['status' => 'error'], 500);
    }
});
```

### Ejemplo: Procesar Reembolso

```php
use Solivellaluisaberto\PayKit\Facades\PayKit;
use Solivellaluisaberto\PayKit\Enums\Currency;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;

$gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');

// Reembolso total
$result = $gateway->refund('ORD-12345');

// Reembolso parcial
$result = $gateway->refund('ORD-12345', 50.00, Currency::EUR);

if ($result->success) {
    echo "Reembolso procesado: {$result->transactionId}";
}
```

## 📚 Documentación Completa

### Clases Principales

#### `PayKit` (Manager Principal)

El manager central que gestiona todos los gateways de pago. Implementa un patrón Factory con cacheo automático.

**Métodos principales:**

- `driver(PaymentProvider|string|class-string $provider, ?string $method = null): PaymentGateway` - Obtener instancia de gateway
- `extend(string $name, callable $driver): void` - Registrar driver personalizado

**Ejemplo:**

```php
use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;
// Usando clase concreta (recomendado - más type-safe)
$gateway = PayKit::driver(RedsysRedirectPaymentService::class);

// Usando enum + method
$gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');

// Usando string + method
$gateway = PayKit::driver('redsys', 'bizum');

// Sin especificar method (usa el primero disponible)
$gateway = PayKit::driver(PaymentProvider::REDSYS);
```

#### `PaymentRequestData` (DTO de Solicitud)

Data Transfer Object inmutable que encapsula todos los datos necesarios para iniciar un pago.

**Propiedades:**

- `float $amount` - Monto del pago (debe ser > 0 y <= 999999.99)
- `Currency $currency` - Moneda del pago
- `string $orderId` - Identificador único del pedido (máx. 255 caracteres)
- `array $metadata` - Metadatos adicionales (description, customer_email, etc.)
- `?string $returnUrl` - URL de retorno después del pago
- `?string $cancelUrl` - URL de cancelación
- `?string $notificationUrl` - URL para notificaciones asíncronas

**Ejemplo:**

```php
$request = new PaymentRequestData(
    amount: 150.50,
    currency: Currency::EUR,
    orderId: 'ORD-2024-001',
    returnUrl: 'https://example.com/payment/success',
    cancelUrl: 'https://example.com/payment/cancel',
    notificationUrl: 'https://example.com/payment/webhook',
    metadata: [
        'description' => 'Compra de productos premium',
        'customer_email' => 'cliente@example.com',
        'customer_id' => '12345'
    ]
);
```

#### `PaymentResponseData` (DTO de Respuesta)

Contiene la respuesta del gateway después de iniciar un pago.

**Propiedades:**

- `PaymentType $type` - Tipo de respuesta (FORM, REDIRECT, etc.)
- `array $data` - Datos adicionales de la respuesta
- `?string $formHtml` - HTML del formulario (si type es FORM)

#### `PaymentResultData` (DTO de Resultado)

Contiene el resultado final de una operación de pago (callback, reembolso, etc.).

**Propiedades:**

- `bool $success` - Indica si la operación fue exitosa
- `string $status` - Estado del pago (completed, failed, refunded, etc.)
- `?string $paymentId` - ID del pago
- `?string $transactionId` - ID de la transacción
- `?string $message` - Mensaje descriptivo
- `?array $data` - Datos adicionales

**Métodos útiles:**

- `isCompleted(): bool` - Verifica si el pago está completado
- `isFailed(): bool` - Verifica si el pago falló
- `getIdentifier(): ?string` - Obtiene el identificador principal (transactionId o paymentId)

### Enums

#### `PaymentProvider`

Enum que define los proveedores de pago soportados:

```php
PaymentProvider::REDSYS  // Redsys (TPV Virtual)
PaymentProvider::STRIPE  // Stripe (preparado para futuro)
PaymentProvider::PAYPAL  // PayPal (preparado para futuro)
PaymentProvider::CASH    // Commerce/Cash (no soporta pagos online)
```

#### `Currency`

Enum con 40+ monedas soportadas y sus códigos ISO 4217:

```php
Currency::EUR  // Euro
Currency::USD  // Dólar estadounidense
Currency::GBP  // Libra esterlina
// ... y 37 más
```

**Métodos:**

- `getISO4217(): string` - Obtiene el código numérico ISO 4217
- `getName(): string` - Obtiene el nombre descriptivo
- `tryFromString(string $currency): ?self` - Crea instancia desde string de forma segura

#### `PaymentType`

Enum que define los tipos de respuesta de pago:

```php
PaymentType::FORM     // Formulario HTML para renderizar
PaymentType::REDIRECT // URL de redirección
```

## 💳 Servicios de Pago

### Redsys

Redsys es el principal proveedor de pago soportado actualmente, con dos métodos de pago disponibles.

#### `RedsysRedirectPaymentService`

Servicio para pagos con tarjeta de crédito/débito mediante redirección a TPV Virtual de Redsys.

**Características:**

- ✅ Soporte para tarjetas de crédito y débito
- ✅ Integración con TPV Virtual de Redsys
- ✅ Generación automática de formularios HTML
- ✅ Verificación de callbacks con firma de seguridad
- ✅ Soporte para reembolsos a través de API REST
- ✅ Entornos de prueba y producción

**Uso:**

```php
use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;

$gateway = PayKit::driver(RedsysRedirectPaymentService::class);
// o
$gateway = PayKit::driver(PaymentProvider::REDSYS, 'redirect');
```

#### `RedsysBizumPaymentService`

Servicio para pagos con Bizum, sistema de pagos móviles instantáneos.

**Características:**

- ✅ Soporte para bizum
- ✅ Integración con TPV Virtual de Redsys
- ✅ Generación automática de formularios HTML
- ✅ Verificación de callbacks con firma de seguridad
- ✅ Soporte para reembolsos a través de API REST
- ✅ Entornos de prueba y producción

**Uso:**

```php
use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysBizumPaymentService;
use \Solivellaluisaberto\PayKit\Enums\PaymentProvider;

$gateway = PayKit::driver(RedsysBizumPaymentService::class);
// o
$gateway = PayKit::driver(PaymentProvider::REDSYS, 'bizum');
```

### `RedsysPaymentService` (Clase Base)

Clase abstracta base que contiene toda la lógica común para los servicios de Redsys. Las clases concretas (`RedsysRedirectPaymentService` y `RedsysBizumPaymentService`) extienden esta clase.

**Métodos principales:**

- `initiate(PaymentRequestData $request): PaymentResponseData` - Iniciar un nuevo pago
- `verifyCallback(array $postData): PaymentResultData` - Verificar y procesar callback de Redsys
- `refund(string $paymentId, ?float $amount = null, ?Currency $currency = null): PaymentResultData` - Procesar reembolso
- `capture(string $paymentId): PaymentResultData` - Capturar pago (Redsys no requiere captura separada)
- `getStatus(string $paymentId): PaymentResultData` - Obtener estado (no soportado por Redsys directamente)

## 🔧 Ejemplos Avanzados

### Ejemplo Completo: Flujo de Pago Completo

```php
namespace App\Http\Controllers;

use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;
use Solivellaluisaberto\PayKit\Facades\PayKit;
use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Enums\Currency;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Request $request)
    {
        // Validar datos del formulario
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'order_id' => 'required|string|max:255',
        ]);

        // Crear solicitud de pago
        $paymentRequest = new PaymentRequestData(
            amount: (float) $validated['amount'],
            currency: Currency::EUR,
            orderId: $validated['order_id'],
            returnUrl: route('payment.return'),
            cancelUrl: route('payment.cancel'),
            notificationUrl: route('payment.webhook'),
            metadata: [
                'description' => 'Compra en tienda online',
                'customer_email' => auth()->user()->email,
            ]
        );

        // Obtener gateway
        $gateway = PayKit::driver(
            RedsysRedirectPaymentService::class
        );

        // Iniciar pago
        $response = $gateway->initiate($paymentRequest);

        // Guardar información del pago en BD
        Payment::create([
            'order_id' => $validated['order_id'],
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        // Renderizar formulario
        return view('payment.form', [
            'formHtml' => $response->formHtml
        ]);
    }

    public function handleReturn(Request $request)
    {
        $gateway = PayKit::driver(
            RedsysRedirectPaymentService::class
        );

        try {
            $result = $gateway->verifyCallback($request->all());

            if ($result->success && $result->isCompleted()) {
                // Actualizar pago en BD
                Payment::where('order_id', $result->paymentId)
                    ->update([
                        'status' => 'completed',
                        'transaction_id' => $result->transactionId,
                        'completed_at' => now(),
                    ]);

                return redirect()->route('payment.success')
                    ->with('message', 'Pago completado exitosamente');
            } else {
                return redirect()->route('payment.failed')
                    ->with('error', 'El pago no pudo ser procesado');
            }
        } catch (\Exception $e) {
            \Log::error('Payment return error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'Error al procesar el pago');
        }
    }

    public function handleWebhook(Request $request)
    {
        $gateway = PayKit::driver(
            RedsysRedirectPaymentService::class
        );

        try {
            $result = $gateway->verifyCallback($request->all());

            // Procesar webhook de forma asíncrona
            dispatch(new ProcessPaymentWebhook($result));

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Payment webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
```

### Ejemplo: Reembolso con Manejo de Errores

```php
use \Solivellaluisaberto\PayKit\Services\Redsys\RedsysRedirectPaymentService;
use Solivellaluisaberto\PayKit\Facades\PayKit;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;

try {
    $gateway = PayKit::driver(
        RedsysRedirectPaymentService::class
    );

    $result = $gateway->refund('ORD-12345', 50.00, Currency::EUR);

    if ($result->success) {
        // Reembolso exitoso
        Order::where('order_id', 'ORD-12345')
            ->update([
                'refunded_amount' => 50.00,
                'refund_transaction_id' => $result->transactionId,
                'refunded_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Reembolso procesado correctamente',
            'transaction_id' => $result->transactionId
        ]);
    }
} catch (PaymentProviderException $e) {
    // Error específico del proveedor
    return response()->json([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], 400);
} catch (\Exception $e) {
    // Error general
    \Log::error('Refund error', ['error' => $e->getMessage()]);
    return response()->json([
        'success' => false,
        'error' => 'Error al procesar el reembolso'
    ], 500);
}
```

## 🔌 Extensibilidad

### Registrar un Driver Personalizado

Puedes extender el sistema agregando tus propios proveedores de pago:

```php
use Solivellaluisaberto\PayKit\Facades\PayKit;
use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;

// En un Service Provider (AppServiceProvider o PaymentServiceProvider)
PayKit::extend('mercadopago', function ($payKit) {
    return new MercadoPagoService(config('payments.mercadopago.key'));
});

// O para un método específico
PayKit::extend('redsys.custom', function ($payKit) {
    return new CustomRedsysService();
});

// Usar el driver personalizado
$gateway = PayKit::driver('mercadopago');
```

### Crear un Servicio Personalizado

Para crear tu propio servicio de pago, implementa el contrato `PaymentGateway`:

```php
namespace App\Services\Payments;

use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Data\PaymentResponseData;
use Solivellaluisaberto\PayKit\Data\PaymentResultData;

class CustomPaymentService implements PaymentGateway
{
    public function initiate(PaymentRequestData $request): PaymentResponseData
    {
        // Implementar lógica de inicio de pago
    }

    public function verifyCallback(array $postData): PaymentResultData
    {
        // Implementar verificación de callback
    }

    public function refund(string $paymentId, ?float $amount = null, ?Currency $currency = null): PaymentResultData
    {
        // Implementar lógica de reembolso
    }

    public function capture(string $paymentId): PaymentResultData
    {
        // Implementar captura si es necesario
    }

    public function getStatus(string $paymentId): PaymentResultData
    {
        // Implementar consulta de estado
    }
}
```

## 🧪 Testing

El paquete incluye una suite completa de tests con **124 tests** y **359 assertions**, cubriendo:

- ✅ Tests unitarios para todas las clases principales
- ✅ Tests de integración para flujos completos
- ✅ Tests de validación de datos
- ✅ Tests de manejo de excepciones
- ✅ Tests de extensibilidad

### Ejecutar Tests

```bash
# Ejecutar todos los tests
vendor/bin/phpunit

# Ejecutar tests específicos
vendor/bin/phpunit --filter PaymentRequestDataTest

# Con cobertura de código
vendor/bin/phpunit --coverage-html coverage
```

### Estado de Tests

```
OK (124 tests, 359 assertions)
```

**Cobertura de Tests:**

- ✅ `PayKit` (Manager) - 24 tests
- ✅ `PaymentRequestData` - Tests completos
- ✅ `PaymentResponseData` - Tests completos
- ✅ `PaymentResultData` - Tests completos
- ✅ `Currency` Enum - 8 tests
- ✅ `PaymentProvider` Enum - Tests completos
- ✅ Excepciones - Tests completos
- ✅ Servicios Redsys - Tests completos

## 📝 Excepciones

El paquete incluye un sistema robusto de excepciones para manejar diferentes tipos de errores:

### `PaymentConfigurationException`

Se lanza cuando hay problemas de configuración:

```php
// Credenciales faltantes
PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');

// Entorno inválido
PaymentConfigurationException::invalidEnvironment('Redsys', 'invalid-env');

// Proveedor no soportado
PaymentConfigurationException::unsupportedProvider('UnknownProvider');
```

### `PaymentProviderException`

Se lanza cuando hay errores del proveedor de pago:

```php
// Error de API
PaymentProviderException::apiError(PaymentProvider::REDSYS, 'Error message');

// Pago rechazado
PaymentProviderException::paymentDeclined(PaymentProvider::REDSYS, 'Reason');

// Verificación de firma fallida
PaymentProviderException::signatureVerificationFailed(PaymentProvider::REDSYS);
```

### `PaymentValidationException`

Se lanza cuando hay errores de validación de datos:

```php
// Monto inválido
PaymentValidationException::invalidAmount(0);

// Order ID inválido
PaymentValidationException::invalidOrderId('');

// URL inválida
PaymentValidationException::invalidReturnUrl('not-a-url');
```

## 🛠️ Estructura del Paquete

```
laravel-pay-kit/
├── config/
│   └── pay-kit.php              # Configuración del paquete
├── src/
│   ├── Concerns/
│   │   └── LogsPayments.php     # Trait para logging
│   ├── Contracts/
│   │   └── PaymentGateway.php   # Contrato principal
│   ├── Data/
│   │   ├── PaymentRequestData.php
│   │   ├── PaymentResponseData.php
│   │   └── PaymentResultData.php
│   ├── Enums/
│   │   ├── Currency.php
│   │   ├── PaymentProvider.php
│   │   └── PaymentType.php
│   ├── Exceptions/
│   │   ├── PaymentConfigurationException.php
│   │   ├── PaymentProviderException.php
│   │   └── PaymentValidationException.php
│   ├── Facades/
│   │   └── PayKit.php
│   ├── Services/
│   │   └── Redsys/
│   │       ├── RedsysPaymentService.php      # Clase base
│   │       ├── RedsysRedirectPaymentService.php
│   │       └── RedsysBizumPaymentService.php
│   ├── PayKit.php               # Manager principal
│   └── PayKitServiceProvider.php
└── tests/
    ├── Feature/                 # Tests de integración
    └── Unit/                    # Tests unitarios
```

## 🤝 Contribución

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir PSR-12 para estilo de código
- Añadir tests para nuevas funcionalidades
- Actualizar documentación cuando sea necesario
- Mantener cobertura de tests por encima del 90%

## 📄 Licencia

Este paquete está licenciado bajo la [Licencia MIT](LICENSE).

## 🆘 Soporte

Para reportar bugs o solicitar features, por favor abre un issue en el repositorio.

## 🙏 Agradecimientos

- Laravel Framework
- Redsys por su TPV Virtual
- Todos los contribuidores

---

**Desarrollado con ❤️ para la comunidad Laravel**
