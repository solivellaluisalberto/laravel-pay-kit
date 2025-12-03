# Laravel Pay Kit

Paquete de Laravel para integración de pagos.

## Instalación

Puedes instalar el paquete a través de Composer:

```bash
composer require solivellaluisaberto/laravel-pay-kit
```

## Configuración

Publica el archivo de configuración:

```bash
php artisan vendor:publish --provider="Solivellaluisaberto\PayKit\PayKitServiceProvider" --tag="config"
```

Esto creará el archivo `config/pay-kit.php` donde puedes configurar las opciones del paquete.

## Uso

```php
use Solivellaluisaberto\PayKit\Facades\PayKit;

// Ejemplo de uso
$payment = PayKit::processPayment($amount, $data);
```

## Documentación

Para más información, consulta la [documentación completa](docs/README.md).

## Licencia

Este paquete está licenciado bajo la [Licencia MIT](LICENSE).

