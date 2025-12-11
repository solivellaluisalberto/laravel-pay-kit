<?php

namespace Solivellaluisaberto\PayKit\Services\Redsys;

use Sermepa\Tpv\Tpv;
use Sermepa\Tpv\TpvException;
use Solivellaluisaberto\PayKit\Concerns\LogsPayments;
use Solivellaluisaberto\PayKit\Contracts\PaymentGateway;
use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Data\PaymentResponseData;
use Solivellaluisaberto\PayKit\Data\PaymentResultData;
use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Solivellaluisaberto\PayKit\Enums\PaymentType;
use Solivellaluisaberto\PayKit\Enums\Currency;
use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;

/**
 * Tipos de transacción soportados por Redsys
 *
 * Define los diferentes tipos de operaciones que pueden realizarse
 * a través de la pasarela de pago Redsys.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 */
enum RedsysTransactionType: string
{
    /** Autorización de pago (sin captura inmediata) */
    case AUTHORIZATION = '0';
    /** Reembolso de pago */
    case REFUND = '3';
}

/**
 * Métodos de pago soportados por Redsys
 *
 * Define los diferentes métodos de pago que pueden utilizarse
 * con la pasarela de pago Redsys.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 */
enum RedsysPaymentMethod: string
{
    /** Pago con tarjeta de crédito/débito */
    case CARD = 'T';
    
    /** Pago con Bizum */
    case BIZUM = 'z';
}

/**
 * Entornos de operación de Redsys (TPV Virtual)
 *
 * Define los entornos disponibles para la pasarela TPV Virtual de Redsys.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 */
enum RedsysEnvironment: string
{
    /** Entorno de pruebas */
    case TEST = 'test';
    
    /** Entorno de producción */
    case LIVE = 'live';
}

/**
 * Entornos de operación de Redsys (API REST)
 *
 * Define los entornos disponibles para la API REST de Redsys,
 * utilizada para operaciones como reembolsos.
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 */
enum RedsysEnvironmentRest: string
{
    /** Entorno de pruebas para API REST */
    case TEST = 'restTest';
    
    /** Entorno de producción para API REST */
    case LIVE = 'restLive';
}

/**
 * Servicio base abstracto para integración con Redsys
 *
 * Esta clase abstracta proporciona la implementación base para interactuar
 * con la pasarela de pago Redsys. Implementa el contrato `PaymentGateway`
 * y proporciona funcionalidad común para todos los métodos de pago de Redsys
 * (tarjeta, Bizum, etc.).
 *
 * Las clases concretas (como `RedsysRedirectPaymentService` o `RedsysBizumPaymentService`)
 * deben extender esta clase y definir el método de pago específico en su constructor.
 *
 * Características principales:
 * - Integración con TPV Virtual de Redsys para pagos
 * - Integración con API REST de Redsys para reembolsos
 * - Verificación de firmas para seguridad
 * - Soporte para entornos de prueba y producción
 * - Logging completo de todas las operaciones
 *
 * @package Solivellaluisaberto\PayKit\Services\Redsys
 * @author Solivellaluisaberto
 */
abstract class RedsysPaymentService implements PaymentGateway
{
    use LogsPayments;

    /**
     * Código de comercio proporcionado por Redsys
     *
     * Identificador único del comercio en el sistema Redsys.
     * Es requerido para todas las operaciones.
     *
     * @var string
     */
    private string $merchantCode;

    /**
     * Clave secreta para firmar las transacciones
     *
     * Clave secreta proporcionada por Redsys utilizada para generar
     * y verificar las firmas de las transacciones. Es crítica para
     * la seguridad de las operaciones.
     *
     * @var string
     */
    private string $secretKey;

    /**
     * Número de terminal de Redsys
     *
     * Identificador del terminal de pago. Por defecto es '1'.
     *
     * @var string
     */
    private string $terminal;

    /**
     * Entorno de operación para TPV Virtual
     *
     * Define si se utiliza el entorno de pruebas o producción
     * para las operaciones de TPV Virtual (iniciar pagos).
     *
     * @var RedsysEnvironment
     */
    private RedsysEnvironment $environment;

    /**
     * Entorno de operación para API REST
     *
     * Define si se utiliza el entorno de pruebas o producción
     * para las operaciones de API REST (reembolsos).
     *
     * @var RedsysEnvironmentRest
     */
    private RedsysEnvironmentRest $environmentRest;

    /**
     * Método de pago utilizado por esta instancia
     *
     * Define el método de pago específico (tarjeta, Bizum, etc.)
     * que utilizará esta instancia del servicio. Debe ser establecido
     * por las clases concretas en su constructor.
     *
     * @var RedsysPaymentMethod
     */
    protected RedsysPaymentMethod $paymentMethod;

    /**
     * Constructor de RedsysPaymentService
     *
     * Carga credenciales y configuración desde `config('pay-kit.redsys')`,
     * inicializa el entorno (TPV y REST) y valida que existan `merchant_code`
     * y `secret_key`. El entorno debe ser 'test' o 'live'; cualquier otro valor
     * en configuración lanza `PaymentConfigurationException::invalidEnvironment`.
     *
     * @throws PaymentConfigurationException Si falta `merchant_code`, falta `secret_key`
     *                                       o el entorno configurado no es válido.
     *
     * @example
     * ```php
     * // Instanciar usando la configuración cargada desde config/pay-kit.php
     * $service = new RedsysRedirectPaymentService();
     * ```
     */
    public function __construct() {
        static $config = null;
        
        if ($config === null) {
            $config = config('pay-kit.redsys', []);
        }
        
        $this->merchantCode = $config['merchant_code'] ?? null;
        $this->secretKey = $config['secret_key'] ?? null;
        $this->terminal = $config['terminal'] ?? '1';
        
        // Manejar environment: puede venir como parámetro, de config, o ser null
        $envValue = $config['environment'] ?? null;
        
        // Convertir string a enum si es necesario
        if (is_string($envValue)) {
            try {
                $this->environment = RedsysEnvironment::from($envValue);
            } catch (\ValueError $e) {
                throw PaymentConfigurationException::invalidEnvironment('Redsys', $envValue);
            }
        } elseif ($envValue instanceof RedsysEnvironment) {
            $this->environment = $envValue;
        }
        
        // Configurar environmentRest basado en el environment
        $this->environmentRest = $this->environment === RedsysEnvironment::LIVE 
            ? RedsysEnvironmentRest::LIVE 
            : RedsysEnvironmentRest::TEST;

        if (!$this->merchantCode) {
            throw PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');
        }
        if (!$this->secretKey) {
            throw PaymentConfigurationException::missingCredentials('Redsys', 'secret_key');
        }
    }

    /**
     * Inicia un nuevo proceso de pago con Redsys
     *
     * Este método genera un formulario HTML que debe ser renderizado y
     * auto-enviado al usuario para completar el pago en la pasarela de Redsys.
     * El formulario incluye todos los datos necesarios y la firma de seguridad.
     *
     * El proceso funciona de la siguiente manera:
     * 1. Se configuran todos los parámetros de la transacción
     * 2. Se genera la firma de seguridad utilizando la clave secreta
     * 3. Se crea el formulario HTML con todos los datos
     * 4. El usuario completa el pago en la pasarela de Redsys
     * 5. Redsys redirige al usuario de vuelta con el resultado
     *
     * @param PaymentRequestData $request Datos de la solicitud de pago
     *
     * @return PaymentResponseData Respuesta con el formulario HTML listo para renderizar.
     *                             El tipo será siempre `PaymentType::FORM` y contendrá
     *                             el HTML del formulario en `formHtml`.
     *
     * @throws PaymentProviderException Si hay un error al generar el formulario:
     *                                  - Error de la librería TPV de Redsys
     *                                  - Error al generar la firma
     *                                  - Error al crear el formulario
     *
     * @example
     * ```php
     * $request = new PaymentRequestData(
     *     amount: 99.99,
     *     currency: Currency::EUR,
     *     orderId: 'ORD-12345',
     *     returnUrl: 'https://example.com/payment/return'
     * );
     *
     * $response = $service->initiate($request);
     *
     * // Renderizar el formulario en la vista
     * return view('payment.form', ['formHtml' => $response->formHtml]);
     * ```
     */
    public function initiate(PaymentRequestData $request): PaymentResponseData
    {
        $this->validateUrls($request);

        $this->logPaymentAttempt(PaymentProvider::REDSYS, $request);

        try {
            $tpv = new Tpv;

            // Configuración básica de la transacción
            $tpv->setAmount($request->amount);
            $tpv->setOrder($request->orderId);
            $tpv->setMerchantcode($this->merchantCode);
            $tpv->setCurrency($request->currency->getISO4217());
            $tpv->setTransactiontype(RedsysTransactionType::AUTHORIZATION->value);
            $tpv->setTerminal($this->terminal);
            $tpv->setVersion('HMAC_SHA256_V1');

            // URLs de retorno (obligatorias para Redsys)
            // URL de éxito: cuando el pago se completa correctamente
            $tpv->setUrlOK($request->returnUrl);
            
            // URL de cancelación/error: cuando el usuario cancela o el pago falla
            $tpv->setUrlKO($request->cancelUrl);

            // URL de notificación: cuando Redsys envía la notificación de pago
            $tpv->setNotification($request->notificationUrl);

            // Método de pago específico (tarjeta, Bizum, etc.)
            $tpv->setMethod($this->paymentMethod->value);

            // Descripción del producto (obtenida de metadata o generada)
            $tpv->setProductDescription($request->metadata['description'] ?? 'Pedido '.$request->orderId);

            // Entorno de operación (test o live)
            $tpv->setEnvironment($this->environment->value);

            // Generar y establecer la firma de seguridad
            $signature = $tpv->generateMerchantSignature($this->secretKey);
            $tpv->setMerchantSignature($signature);

            // Generar el formulario HTML completo
            $formHtml = $tpv->createForm();

            $response = new PaymentResponseData(
                type: PaymentType::FORM,
                data: [
                    'order_id' => $request->orderId,
                    'amount' => $request->amount,
                    'merchant_code' => $this->merchantCode,
                    'payment_method' => $this->paymentMethod->value,
                ],
                formHtml: $formHtml
            );

            $this->logPaymentInitiated(PaymentProvider::REDSYS, $request, $response);

            return $response;
        } catch (TpvException $e) {
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $request->orderId);

            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                $e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Captura o confirma un pago previamente autorizado
     *
     * **Nota**: Redsys no requiere una captura separada, ya que los pagos
     * se confirman automáticamente cuando el usuario completa el pago en
     * la pasarela. Este método existe para cumplir con el contrato
     * `PaymentGateway` y puede utilizarse para verificar el estado de
     * un pago después de recibir el callback.
     *
     * Para verificar el estado real de un pago, se debe utilizar el
     * método `verifyCallback()` con los datos recibidos de Redsys.
     *
     * @param string $paymentId Identificador del pago a capturar
     *
     * @return PaymentResultData Resultado indicando que el pago está confirmado.
     *                          Siempre retorna `success: true` y `status: 'completed'`
     *
     * @example
     * ```php
     * $result = $service->capture('ORD-12345');
     * // Siempre retorna success: true, status: 'completed'
     * ```
     */
    public function capture(string $paymentId): PaymentResultData
    {
        return new PaymentResultData(
            success: true,
            status: 'completed',
            transactionId: $paymentId,
            message: 'Redsys payment confirmed.'
        );
    }

    /**
     * Reembolsa total o parcialmente un pago
     *
     * Este método procesa un reembolso utilizando la API REST de Redsys.
     * Puede realizar un reembolso total (si `$amount` es `null`) o parcial
     * (si se especifica un monto menor al monto original).
     *
     * El proceso funciona de la siguiente manera:
     * 1. Se configuran los parámetros del reembolso
     * 2. Se genera la firma de seguridad
     * 3. Se envía la solicitud a la API REST de Redsys
     * 4. Se verifica la respuesta y la firma
     * 5. Se retorna el resultado del reembolso
     *
     * **Importante**: La moneda (`$currency`) es requerida para procesar el reembolso.
     * Si no se proporciona, se utilizará la moneda por defecto de la configuración.
     *
     * @param string $paymentId Identificador del pago original a reembolsar.
     *                         Este es el mismo ID que se utilizó al crear el pago
     * @param float|null $amount Monto a reembolsar. Si es `null`, se reembolsa
     *                          el monto total del pago original. Si se especifica,
     *                          debe ser mayor que 0 y menor o igual al monto original
     * @param Currency|null $currency Moneda del reembolso. Si es `null`, se utiliza
     *                                la moneda por defecto de la configuración
     *
     * @return PaymentResultData Resultado del reembolso, incluyendo:
     *                          - `success`: `true` si el reembolso fue exitoso
     *                          - `status`: 'refunded' si fue exitoso
     *                          - `transactionId`: ID de la transacción de reembolso
     *                          - `message`: Mensaje descriptivo del resultado
     *
     * @throws PaymentProviderException Si:
     *                                  - La API REST de Redsys retorna un error (`errorCode`)
     *                                  - La verificación de firma falla
     *                                  - El código de respuesta de Redsys es mayor a 99 (reembolso rechazado)
     *                                  - Hay un error de comunicación con la API
     *                                  - Hay un error al procesar la respuesta
     *
     * @example
     * ```php
     * // Reembolso total
     * $result = $service->refund('ORD-12345');
     *
     * // Reembolso parcial
     * $result = $service->refund('ORD-12345', 50.00, Currency::EUR);
     *
     * if ($result->success) {
     *     echo "Reembolso procesado: {$result->transactionId}";
     * }
     * ```
     */
    public function refund(string $paymentId, ?float $amount = null, ?Currency $currency = null): PaymentResultData
    {
        $this->logRefundAttempt(PaymentProvider::REDSYS, $paymentId, $amount);

        try {
            // Obtener la moneda por defecto si no se proporciona
            if ($currency === null) {
                $currency = Currency::tryFromString(config('pay-kit.currency', 'EUR')) ?? Currency::EUR;
            }

            $tpv = new Tpv;
            $tpv->setAmount($amount);
            $tpv->setOrder($paymentId);
            $tpv->setMerchantcode($this->merchantCode);
            $tpv->setCurrency($currency->getISO4217());
            $tpv->setTransactiontype(RedsysTransactionType::REFUND->value);
            $tpv->setTerminal($this->terminal);
            $tpv->setVersion('HMAC_SHA256_V1');
            $tpv->setEnvironment($this->environmentRest->value);

            // Generar y establecer la firma de seguridad
            $signature = $tpv->generateMerchantSignature($this->secretKey);
            $tpv->setMerchantSignature($signature);

            // Enviar la solicitud a la API REST de Redsys
            $response = json_decode($tpv->send(), true);

            // Verificar si la API retornó un error
            if (isset($response['errorCode'])) {
                throw PaymentProviderException::apiError(
                    PaymentProvider::REDSYS,
                    'Refund failed',
                    $response['errorCode']
                );
            }

            // Decodificar los parámetros de la respuesta
            $parameters = $tpv->getMerchantParameters($response['Ds_MerchantParameters']);
            $dsResponse = (int) $parameters['Ds_Response'];

            // Verificar la firma y el código de respuesta (0-99 = exitoso)
            if ($tpv->check($this->secretKey, $response) && $dsResponse <= 99) {
                $result = new PaymentResultData(
                    success: true,
                    status: 'refunded',
                    transactionId: $parameters['Ds_AuthorisationCode'] ?? $paymentId,
                    message: 'Refund processed successfully.'
                );

                $this->logRefundSuccess(PaymentProvider::REDSYS, $result);

                return $result;
            } else {
                throw PaymentProviderException::refundNotAvailable(
                    PaymentProvider::REDSYS,
                    'Response code: '.$dsResponse
                );
            }
        } catch (TpvException $e) {
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $paymentId);
            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                $e->getMessage(),
                null,
                $e
            );
        } catch (PaymentProviderException $e) {
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $paymentId);
            throw $e;
        } catch (\Exception $e) {
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $paymentId);

            throw PaymentProviderException::apiError(
                PaymentProvider::REDSYS,
                'Error processing refund: '.$e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Obtiene el estado actual de un pago
     *
     * **Nota**: Redsys no proporciona una API REST para consultar el estado
     * de un pago directamente. El estado del pago solo puede obtenerse a través
     * del callback de notificación que Redsys envía después de que el usuario
     * completa el pago.
     *
     * Este método existe para cumplir con el contrato `PaymentGateway`, pero
     * siempre retorna un estado 'unavailable'. Para obtener el estado real de
     * un pago, se debe utilizar el método `verifyCallback()` con los datos
     * recibidos de Redsys.
     *
     * @param string $paymentId Identificador del pago a consultar
     *
     * @return PaymentResultData Siempre retorna `success: false` y `status: 'unavailable'`
     *                          con un mensaje indicando que esta operación no está soportada
     *
     * @example
     * ```php
     * $result = $service->getStatus('ORD-12345');
     * // Siempre retorna success: false, status: 'unavailable'
     * ```
     */
    public function getStatus(string $paymentId): PaymentResultData
    {
        return new PaymentResultData(
            success: false,
            status: 'unavailable',
            message: 'Redsys does not support direct status queries. Use notification callback.'
        );
    }

    /**
     * Verifica y procesa un callback de Redsys
     *
     * Este método procesa la respuesta asíncrona que Redsys envía después de que
     * el usuario completa el pago. Es el método principal para determinar el estado
     * final de un pago en Redsys.
     *
     * El proceso de verificación incluye:
     * 1. Validación de que los parámetros requeridos estén presentes
     * 2. Verificación de la firma de seguridad (crítica para prevenir fraude)
     * 3. Decodificación de los parámetros de la respuesta
     * 4. Interpretación del código de respuesta de Redsys
     * 5. Retorno del resultado del pago
     *
     * **Códigos de respuesta de Redsys:**
     * - 0-99: Pago exitoso
     * - 100+: Pago rechazado o fallido
     * - 9999: Error en la transacción
     *
     * **Seguridad**: La verificación de la firma es crítica. Si la firma no coincide,
     * se lanza una excepción `PaymentProviderException::signatureVerificationFailed()`
     * ya que esto podría indicar un intento de fraude.
     *
     * @param array<string, mixed> $postData Datos POST recibidos de Redsys.
     *                                      Debe contener al menos:
     *                                      - `Ds_MerchantParameters`: Parámetros codificados de la respuesta
     *                                      - `Ds_Signature`: Firma de seguridad de la respuesta
     *
     * @return PaymentResultData Resultado del procesamiento del callback, incluyendo:
     *                          - `success`: `true` si el pago fue exitoso (código 0-99)
     *                          - `status`: 'completed' si fue exitoso, 'failed' en caso contrario
     *                          - `paymentId`: ID del pedido (`Ds_Order`)
     *                          - `transactionId`: Código de autorización (`Ds_AuthorisationCode`)
     *                          - `message`: Mensaje descriptivo del resultado
     *                          - `data`: Todos los parámetros decodificados de la respuesta
     *
     * @throws PaymentProviderException Si:
     *                                  - Faltan parámetros requeridos (`Ds_MerchantParameters` o `Ds_Signature`)
     *                                  - La verificación de firma falla (posible fraude)
     *                                  - El pago fue rechazado por el banco (código > 99)
     *                                  - Hay un error al decodificar los parámetros
     *                                  - Hay un error al procesar el callback
     *
     * @example
     * ```php
     * // En una ruta de callback (ej: Route::post('/payment/redsys/callback', ...))
     * public function handleCallback(Request $request)
     * {
     *     $service = PayKit::driver('redsys', 'card');
     *     $result = $service->verifyCallback($request->all());
     *
     *     if ($result->success && $result->isCompleted()) {
     *         // Pago exitoso, actualizar base de datos
     *         Order::where('order_id', $result->paymentId)
     *             ->update(['status' => 'paid', 'payment_status' => 'completed']);
     *     } else {
     *         // Pago fallido, registrar error
     *         Log::error('Payment failed', ['result' => $result]);
     *     }
     *
     *     return response()->json($result);
     * }
     * ```
     */
    public function verifyCallback(array $postData): PaymentResultData
    {
        $this->logCallbackReceived(PaymentProvider::REDSYS, $postData);

        try {
            $tpv = new Tpv;

            // Validar que los parámetros requeridos estén presentes
            if (!isset($postData['Ds_MerchantParameters']) || !isset($postData['Ds_Signature'])) {
                throw PaymentProviderException::invalidResponse(
                    PaymentProvider::REDSYS,
                    'Missing required parameters'
                );
            }

            // Verificar la firma de seguridad (crítico para prevenir fraude)
            if (!$tpv->check($this->secretKey, $postData)) {
                throw PaymentProviderException::signatureVerificationFailed(PaymentProvider::REDSYS);
            }

            // Decodificar los parámetros de la respuesta
            $parameters = $tpv->getMerchantParameters($postData['Ds_MerchantParameters']);
            $dsResponse = (int) ($parameters['Ds_Response'] ?? 9999);

            // En Redsys, códigos 0-99 indican éxito, 100+ indican fallo
            $success = $dsResponse >= 0 && $dsResponse <= 99;

            // Si el pago fue rechazado, lanzar excepción con el código de respuesta
            if (!$success && $dsResponse !== 9999) {
                throw PaymentProviderException::paymentDeclined(
                    PaymentProvider::REDSYS,
                    'Payment declined by bank',
                    (string) $dsResponse
                );
            }

            $result = new PaymentResultData(
                success: $success,
                status: $success ? 'completed' : 'failed',
                paymentId: $parameters['Ds_Order'] ?? null,
                transactionId: $parameters['Ds_AuthorisationCode'] ?? null,
                message: $success ? 'Payment completed successfully' : 'Payment failed',
                data: $parameters
            );

            // Registrar el resultado según el estado
            if ($result->success) {
                $this->logPaymentSuccess(PaymentProvider::REDSYS, $result);
            } else {
                $this->logPaymentFailed(PaymentProvider::REDSYS, $result);
            }

            return $result;
        } catch (PaymentProviderException $e) {
            // Intentar obtener el orderId de los parámetros para el log
            $orderId = null;
            if (isset($postData['Ds_MerchantParameters'])) {
                try {
                    $tpv = new Tpv;
                    $parameters = $tpv->getMerchantParameters($postData['Ds_MerchantParameters']);
                    $orderId = $parameters['Ds_Order'] ?? null;
                } catch (\Exception $ex) {
                    // Si no se puede decodificar, orderId queda null
                }
            }
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $orderId);
            throw $e;
        } catch (\Exception $e) {
            // Intentar obtener el orderId de los parámetros para el log
            $orderId = null;
            if (isset($postData['Ds_MerchantParameters'])) {
                try {
                    $tpv = new Tpv;
                    $parameters = $tpv->getMerchantParameters($postData['Ds_MerchantParameters']);
                    $orderId = $parameters['Ds_Order'] ?? null;
                } catch (\Exception $ex) {
                    // Si no se puede decodificar, orderId queda null
                }
            }
            $this->logPaymentError(PaymentProvider::REDSYS, $e, $orderId);

            throw PaymentProviderException::invalidResponse(
                PaymentProvider::REDSYS,
                $e->getMessage()
            );
        }
    }

    protected function validateUrls(PaymentRequestData $request): void {
        // Validar que las URLs requeridas por Redsys estén presentes
        if ($request->returnUrl === null) {
            throw PaymentValidationException::missingRequiredField('returnUrl');
        }
        
        if ($request->cancelUrl === null) {
            throw PaymentValidationException::missingRequiredField('cancelUrl');
        }

        if ($request->notificationUrl === null) {
            throw PaymentValidationException::missingRequiredField('notificationUrl');
        }
    }
    
}