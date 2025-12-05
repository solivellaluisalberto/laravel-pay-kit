<?php

namespace Solivellaluisaberto\PayKit\Data;

use Solivellaluisaberto\PayKit\Enums\Currency;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;

/**
 * Data Transfer Object (DTO) para solicitudes de pago
 *
 * Esta clase encapsula todos los datos necesarios para iniciar un proceso de pago,
 * incluyendo validaciones exhaustivas de todos los campos. Actúa como un contrato
 * inmutable entre el código cliente y los servicios de pago.
 *
 * Todas las propiedades son readonly para garantizar la inmutabilidad del objeto
 * una vez creado, lo que previene modificaciones accidentales durante el procesamiento.
 *
 * @package Solivellaluisaberto\PayKit\Data
 * @author Solivellaluisaberto
 */
class PaymentRequestData
{
    /**
     * Moneda en la que se realizará el pago
     *
     * Si no se proporciona en el constructor, se obtiene de la configuración
     * 'pay-kit.currency'. Si la configuración tampoco está disponible o es inválida,
     * se utiliza EUR como valor por defecto.
     *
     * @var Currency
     */
    public readonly Currency $currency;

    /**
     * Constructor de PaymentRequestData
     *
     * Inicializa una nueva instancia de solicitud de pago con validación automática
     * de todos los campos. La validación se ejecuta inmediatamente después de la
     * inicialización para garantizar la integridad de los datos.
     *
     * @param float $amount Monto del pago. Debe ser mayor que 0 y menor o igual a 999999.99
     * @param Currency|string|null $currency Moneda del pago. Puede ser:
     *                                       - Un enum Currency (recomendado)
     *                                       - Un string con código ISO 4217 (ej: 'EUR', 'USD')
     *                                       - null para usar la configuración por defecto
     * @param string $orderId Identificador único del pedido. Máximo 255 caracteres, no puede estar vacío
     * @param array $metadata Metadatos adicionales del pago. Puede incluir:
     *                        - 'description': Descripción del pago (máx. 500 caracteres)
     *                        - 'customer_email': Email del cliente (debe ser válido)
     *                        - Cualquier otro dato personalizado
     * @param string|null $returnUrl URL a la que redirigir después del pago (exitoso o fallido).
     *                              Debe ser una URL válida si se proporciona
     * @param string|null $cancelUrl URL a la que redirigir si el usuario cancela el pago.
     *                              Debe ser una URL válida si se proporciona
     * @param string|null $notificationUrl URL para notificaciones asíncronas del proveedor de pago.
     *                                    Debe ser una URL válida si se proporciona
     *
     * @throws PaymentValidationException Si el monto es inválido (<= 0 o > 999999.99),
     *                                   si el orderId está vacío o excede 255 caracteres,
     *                                   si la currency proporcionada como string no existe,
     *                                   si alguna URL proporcionada no es válida,
     *                                   si la descripción excede 500 caracteres,
     *                                   o si el email del cliente no es válido
     *
     * @example
     * // Uso básico con currency por defecto
     * $request = new PaymentRequestData(
     *     amount: 100.50,
     *     orderId: 'ORD-12345'
     * );
     *
     * // Con currency específica
     * $request = new PaymentRequestData(
     *     amount: 100.50,
     *     currency: Currency::USD,
     *     orderId: 'ORD-12345',
     *     returnUrl: 'https://example.com/payment/return',
     *     metadata: ['description' => 'Compra de productos']
     * );
     */
    public function __construct(
        public readonly float $amount,
        Currency|string|null $currency = null,
        public readonly string $orderId,
        public readonly array $metadata = [],
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?string $notificationUrl = null
    ) {
        // Normalizar y asignar la currency
        $this->currency = $this->normalizeCurrency($currency);
        
        // Validar todos los campos
        $this->validate();
    }

    /**
     * Normalizar el valor de currency a un enum Currency
     *
     * Este método maneja la conversión de diferentes formatos de currency
     * (enum, string, null) a un enum Currency válido, aplicando las reglas
     * de negocio correspondientes según el origen del valor.
     *
     * Reglas de normalización:
     * - null: Lee de configuración 'pay-kit.currency', si es inválida usa EUR
     * - string: Convierte a enum, lanza excepción si no existe
     * - Currency: Se usa directamente sin conversión
     *
     * @param Currency|string|null $currency Valor de currency a normalizar
     *
     * @return Currency Enum Currency normalizado y validado
     *
     * @throws PaymentValidationException Si se proporciona un string que no corresponde
     *                                   a ninguna currency válida del enum Currency
     *
     * @internal Este método es llamado internamente por el constructor.
     *          No debe ser llamado directamente desde código externo.
     */
    private function normalizeCurrency(Currency|string|null $currency): Currency
    {
        // Si no se proporciona currency, usar la de la configuración
        if ($currency === null) {
            $currencyValue = config('pay-kit.currency', 'EUR');
            $currencyEnum = Currency::tryFromString($currencyValue);
            
            // Si la currency de la configuración no existe, usar EUR como fallback
            // (es un valor del sistema, no un error del usuario)
            return $currencyEnum ?? Currency::EUR;
        }

        // Si se proporciona como string, convertir a enum
        if (is_string($currency)) {
            $currencyEnum = Currency::tryFromString($currency);
            
            // Si el usuario proporciona una currency inválida, lanzar excepción
            if ($currencyEnum === null) {
                throw PaymentValidationException::invalidCurrency($currency);
            }
            
            return $currencyEnum;
        }

        // Si ya es un enum Currency, retornarlo directamente
        return $currency;
    }

    /**
     * Validar todos los campos de la solicitud de pago
     *
     * Ejecuta validaciones exhaustivas sobre todos los campos del objeto,
     * garantizando que los datos cumplan con los requisitos del sistema de pagos.
     *
     * Las validaciones incluyen:
     * - Monto: Debe ser > 0 y <= 999999.99
     * - Order ID: No puede estar vacío y máximo 255 caracteres
     * - URLs: Deben ser válidas si se proporcionan
     * - Metadata: Descripción máximo 500 caracteres, email válido si se proporciona
     *
     * @return void
     *
     * @throws PaymentValidationException Si algún campo no cumple con las validaciones:
     *                                   - invalidAmount: Monto inválido
     *                                   - invalidOrderId: Order ID vacío o muy largo
     *                                   - invalidFieldLength: Campo excede longitud máxima
     *                                   - invalidReturnUrl: URL de retorno inválida
     *                                   - validationFailed: URL de cancelación/notificación inválida
     *                                   - invalidEmail: Email del cliente inválido
     *
     * @internal Este método es llamado automáticamente por el constructor.
     *          No debe ser llamado directamente desde código externo.
     */
    private function validate(): void
    {
        $this->validateAmount();
        $this->validateOrderId();
        $this->validateUrls();
        $this->validateMetadata();
    }

    /**
     * Validar el monto del pago
     *
     * Verifica que el monto sea mayor que 0 y no exceda el máximo permitido
     * de 999999.99 (límite común en sistemas de pago).
     *
     * @return void
     *
     * @throws PaymentValidationException Si el monto es <= 0 o > 999999.99
     */
    private function validateAmount(): void
    {
        if ($this->amount <= 0) {
            throw PaymentValidationException::invalidAmount(
                $this->amount,
                'Amount must be greater than 0'
            );
        }

        if ($this->amount > 999999.99) {
            throw PaymentValidationException::invalidAmount(
                $this->amount,
                'Amount exceeds maximum allowed (999999.99)'
            );
        }
    }

    /**
     * Validar el Order ID
     *
     * Verifica que el Order ID no esté vacío y no exceda 255 caracteres,
     * que es el límite común en bases de datos y sistemas de pago.
     *
     * @return void
     *
     * @throws PaymentValidationException Si el Order ID está vacío o excede 255 caracteres
     */
    private function validateOrderId(): void
    {
        if (empty(trim($this->orderId))) {
            throw PaymentValidationException::invalidOrderId(
                $this->orderId,
                'Order ID cannot be empty'
            );
        }

        if (strlen($this->orderId) > 255) {
            throw PaymentValidationException::invalidFieldLength(
                'orderId',
                strlen($this->orderId),
                255
            );
        }
    }

    /**
     * Validar todas las URLs proporcionadas
     *
     * Verifica que las URLs de retorno, cancelación y notificación sean válidas
     * si se proporcionan. Utiliza filter_var con FILTER_VALIDATE_URL para
     * asegurar que cumplan con el formato estándar de URLs.
     *
     * @return void
     *
     * @throws PaymentValidationException Si alguna URL proporcionada no es válida:
     *                                   - invalidReturnUrl: Para returnUrl
     *                                   - validationFailed: Para cancelUrl y notificationUrl
     */
    private function validateUrls(): void
    {
        if ($this->returnUrl !== null && ! filter_var($this->returnUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::invalidReturnUrl($this->returnUrl);
        }

        if ($this->cancelUrl !== null && ! filter_var($this->cancelUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::validationFailed(
                'cancelUrl',
                'Must be a valid URL'
            );
        }

        if ($this->notificationUrl !== null && ! filter_var($this->notificationUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::validationFailed(
                'notificationUrl',
                'Must be a valid URL'
            );
        }
    }

    /**
     * Validar los metadatos del pago
     *
     * Verifica que los metadatos cumplan con las restricciones:
     * - La descripción no debe exceder 500 caracteres
     * - El email del cliente debe ser válido si se proporciona
     *
     * @return void
     *
     * @throws PaymentValidationException Si la descripción excede 500 caracteres
     *                                   o si el email del cliente no es válido
     */
    private function validateMetadata(): void
    {
        // Validar longitud de la descripción si está presente
        if (isset($this->metadata['description']) && strlen($this->metadata['description']) > 500) {
            throw PaymentValidationException::invalidFieldLength(
                'description',
                strlen($this->metadata['description']),
                500
            );
        }

        // Validar email del cliente si está presente
        if (isset($this->metadata['customer_email'])) {
            if (! filter_var($this->metadata['customer_email'], FILTER_VALIDATE_EMAIL)) {
                throw PaymentValidationException::invalidEmail($this->metadata['customer_email']);
            }
        }
    }
}
