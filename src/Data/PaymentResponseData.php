<?php

namespace Solivellaluisaberto\PayKit\Data;

use Solivellaluisaberto\PayKit\Enums\PaymentType;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;

/**
 * Data Transfer Object (DTO) para respuestas de pago
 *
 * Esta clase encapsula la respuesta de un proveedor de pago después de iniciar
 * un proceso de pago. Dependiendo del tipo de pago, puede contener diferentes
 * tipos de datos (URL de redirección, client secret para APIs, HTML de formulario, etc.).
 *
 * La clase garantiza que los datos requeridos estén presentes según el tipo de
 * respuesta, validando la integridad de la información antes de permitir su uso.
 *
 * Tipos de respuesta soportados:
 * - REDIRECT: Requiere redirectUrl válida para redirigir al usuario
 * - API: Requiere clientSecret para integración con APIs del frontend
 * - FORM: Requiere formHtml con el formulario HTML para auto-submit
 *
 * Todas las propiedades son readonly para garantizar la inmutabilidad del objeto
 * una vez creado, lo que previene modificaciones accidentales durante el procesamiento.
 *
 * @package Solivellaluisaberto\PayKit\Data
 * @author Solivellaluisaberto
 */
class PaymentResponseData
{
    /**
     * Tipo de respuesta del proveedor de pago
     *
     * Determina qué campos son requeridos y cómo debe procesarse la respuesta.
     * Los diferentes tipos requieren diferentes campos:
     * - REDIRECT: requiere redirectUrl
     * - API: requiere clientSecret
     * - FORM: requiere formHtml
     *
     * @var PaymentType
     */
    public readonly PaymentType $type;

    /**
     * Datos adicionales de la respuesta
     *
     * Array asociativo con información adicional del proveedor de pago.
     * Puede incluir IDs de transacción, códigos de autorización, información
     * del pedido, etc. Este campo siempre debe contener al menos un elemento.
     *
     * @var array<string, mixed>
     */
    public readonly array $data;

    /**
     * URL de redirección para pagos tipo REDIRECT
     *
     * URL a la que el usuario debe ser redirigido para completar el pago.
     * Solo es requerida cuando el tipo es PaymentType::REDIRECT.
     *
     * @var string|null
     */
    public readonly ?string $redirectUrl;

    /**
     * Client secret para integraciones tipo API
     *
     * Clave secreta proporcionada por el proveedor para autenticar
     * solicitudes desde el frontend. Solo es requerida cuando el tipo
     * es PaymentType::API (ej: Stripe).
     *
     * @var string|null
     */
    public readonly ?string $clientSecret;

    /**
     * HTML del formulario para pagos tipo FORM
     *
     * Código HTML completo del formulario que debe ser renderizado y
     * auto-enviado al proveedor de pago. Solo es requerido cuando el
     * tipo es PaymentType::FORM (ej: Redsys).
     *
     * @var string|null
     */
    public readonly ?string $formHtml;

    /**
     * Constructor de PaymentResponseData
     *
     * Inicializa una nueva instancia de respuesta de pago con validación
     * automática de todos los campos según el tipo especificado.
     *
     * La validación se ejecuta inmediatamente después de la inicialización
     * para garantizar que los datos requeridos estén presentes y sean válidos.
     *
     * @param PaymentType $type Tipo de respuesta del proveedor de pago.
     *                         Determina qué campos son requeridos
     * @param array<string, mixed> $data Datos adicionales de la respuesta.
     *                                  Debe contener al menos un elemento.
     *                                  Puede incluir información como order_id,
     *                                  transaction_id, payment_id, etc.
     * @param string|null $redirectUrl URL de redirección. Requerida si type es REDIRECT.
     *                               Debe ser una URL válida
     * @param string|null $clientSecret Client secret para APIs. Requerido si type es API.
     *                                 No puede estar vacío
     * @param string|null $formHtml HTML del formulario. Requerido si type es FORM.
     *                            No puede estar vacío
     *
     * @throws PaymentValidationException Si:
     *                                   - El tipo REDIRECT no tiene redirectUrl o es inválida
     *                                   - El tipo API no tiene clientSecret o está vacío
     *                                   - El tipo FORM no tiene formHtml o está vacío
     *                                   - El array $data está vacío
     *
     * @example
     * // Respuesta tipo REDIRECT (PayPal)
     * $response = new PaymentResponseData(
     *     type: PaymentType::REDIRECT,
     *     data: ['order_id' => 'ORD-123', 'payment_id' => 'PAY-456'],
     *     redirectUrl: 'https://paypal.com/checkout/...'
     * );
     *
     * // Respuesta tipo API (Stripe)
     * $response = new PaymentResponseData(
     *     type: PaymentType::API,
     *     data: ['payment_intent_id' => 'pi_123'],
     *     clientSecret: 'pi_123_secret_abc'
     * );
     *
     * // Respuesta tipo FORM (Redsys)
     * $response = new PaymentResponseData(
     *     type: PaymentType::FORM,
     *     data: ['order_id' => 'ORD-123'],
     *     formHtml: '<form action="..." method="POST">...</form>'
     * );
     */
    public function __construct(
        PaymentType $type,
        array $data,
        ?string $redirectUrl = null,
        ?string $clientSecret = null,
        ?string $formHtml = null,
    ) {
        $this->type = $type;
        $this->data = $data;
        $this->redirectUrl = $redirectUrl;
        $this->clientSecret = $clientSecret;
        $this->formHtml = $formHtml;

        // Validar todos los campos según el tipo
        $this->validate();
    }

    /**
     * Validar todos los campos de la respuesta según el tipo
     *
     * Ejecuta validaciones específicas según el tipo de respuesta:
     * - REDIRECT: Valida que redirectUrl esté presente y sea válida
     * - API: Valida que clientSecret esté presente y no esté vacío
     * - FORM: Valida que formHtml esté presente y no esté vacío
     *
     * También valida que el array $data no esté vacío, ya que siempre
     * debe contener información adicional del proveedor.
     *
     * @return void
     *
     * @throws PaymentValidationException Si algún campo requerido falta,
     *                                   si alguna URL no es válida,
     *                                   si algún campo requerido está vacío,
     *                                   o si el array $data está vacío
     *
     * @internal Este método es llamado automáticamente por el constructor.
     *          No debe ser llamado directamente desde código externo.
     */
    private function validate(): void
    {
        // Validar que data no esté vacío (siempre requerido)
        $this->validateData();

        // Validar campos específicos según el tipo de respuesta
        match ($this->type) {
            PaymentType::REDIRECT => $this->validateRedirectType(),
            PaymentType::API => $this->validateApiType(),
            PaymentType::FORM => $this->validateFormType(),
        };
    }

    /**
     * Validar que el array de datos no esté vacío
     *
     * El array $data siempre debe contener al menos un elemento con
     * información adicional del proveedor de pago, como IDs de transacción,
     * códigos de autorización, etc.
     *
     * @return void
     *
     * @throws PaymentValidationException Si el array $data está vacío
     */
    private function validateData(): void
    {
        if (empty($this->data)) {
            throw PaymentValidationException::validationFailed(
                'data',
                'Response data cannot be empty'
            );
        }
    }

    /**
     * Validar campos requeridos para tipo REDIRECT
     *
     * Para respuestas tipo REDIRECT, se requiere que redirectUrl esté
     * presente y sea una URL válida, ya que el usuario será redirigido
     * a esta URL para completar el pago.
     *
     * @return void
     *
     * @throws PaymentValidationException Si redirectUrl no está presente
     *                                   o si no es una URL válida
     */
    private function validateRedirectType(): void
    {
        if ($this->redirectUrl === null) {
            throw PaymentValidationException::validationFailed(
                'redirectUrl',
                'REDIRECT type requires redirectUrl'
            );
        }

        if (! filter_var($this->redirectUrl, FILTER_VALIDATE_URL)) {
            throw PaymentValidationException::validationFailed(
                'redirectUrl',
                'Must be a valid URL'
            );
        }
    }

    /**
     * Validar campos requeridos para tipo API
     *
     * Para respuestas tipo API, se requiere que clientSecret esté presente
     * y no esté vacío, ya que se utilizará en el frontend para autenticar
     * las solicitudes al proveedor de pago.
     *
     * @return void
     *
     * @throws PaymentValidationException Si clientSecret no está presente
     *                                   o si está vacío
     */
    private function validateApiType(): void
    {
        if ($this->clientSecret === null) {
            throw PaymentValidationException::missingRequiredField('clientSecret');
        }

        if (empty(trim($this->clientSecret))) {
            throw PaymentValidationException::validationFailed(
                'clientSecret',
                'Cannot be empty'
            );
        }
    }

    /**
     * Validar campos requeridos para tipo FORM
     *
     * Para respuestas tipo FORM, se requiere que formHtml esté presente
     * y no esté vacío, ya que contiene el formulario HTML que debe ser
     * renderizado y auto-enviado al proveedor de pago.
     *
     * @return void
     *
     * @throws PaymentValidationException Si formHtml no está presente
     *                                   o si está vacío
     */
    private function validateFormType(): void
    {
        if ($this->formHtml === null) {
            throw PaymentValidationException::validationFailed(
                'formHtml',
                'FORM type requires formHtml'
            );
        }

        if (empty(trim($this->formHtml))) {
            throw PaymentValidationException::validationFailed(
                'formHtml',
                'Cannot be empty'
            );
        }
    }

    /**
     * Verificar si la respuesta es de tipo REDIRECT
     *
     * Útil para determinar el flujo de procesamiento en el código cliente.
     * Las respuestas REDIRECT requieren redirigir al usuario a la URL proporcionada.
     *
     * @return bool true si el tipo es REDIRECT, false en caso contrario
     *
     * @example
     * if ($response->isRedirect()) {
     *     return redirect($response->redirectUrl);
     * }
     */
    public function isRedirect(): bool
    {
        return $this->type === PaymentType::REDIRECT;
    }

    /**
     * Verificar si la respuesta es de tipo API
     *
     * Útil para determinar el flujo de procesamiento en el código cliente.
     * Las respuestas API requieren pasar el clientSecret al frontend para
     * completar el pago mediante JavaScript.
     *
     * @return bool true si el tipo es API, false en caso contrario
     *
     * @example
     * if ($response->isApi()) {
     *     return view('payment', ['clientSecret' => $response->clientSecret]);
     * }
     */
    public function isApi(): bool
    {
        return $this->type === PaymentType::API;
    }

    /**
     * Verificar si la respuesta es de tipo FORM
     *
     * Útil para determinar el flujo de procesamiento en el código cliente.
     * Las respuestas FORM requieren renderizar el HTML del formulario y
     * auto-enviarlo al proveedor de pago.
     *
     * @return bool true si el tipo es FORM, false en caso contrario
     *
     * @example
     * if ($response->isForm()) {
     *     return view('payment-form', ['formHtml' => $response->formHtml]);
     * }
     */
    public function isForm(): bool
    {
        return $this->type === PaymentType::FORM;
    }
}
