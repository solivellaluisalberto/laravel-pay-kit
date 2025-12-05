<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Exceptions;

use Solivellaluisaberto\PayKit\Exceptions\PaymentException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentValidationExceptionTest extends TestCase
{
    /** @test */
    public function it_extends_payment_exception(): void
    {
        $exception = PaymentValidationException::invalidAmount(100.50);

        $this->assertInstanceOf(PaymentException::class, $exception);
    }

    /** @test */
    public function it_has_correct_http_status_code(): void
    {
        $exception = PaymentValidationException::invalidAmount(100.50);

        $this->assertEquals(422, $exception->getHttpStatusCode());
    }

    /** @test */
    public function it_creates_invalid_amount_exception(): void
    {
        $exception = PaymentValidationException::invalidAmount(100.50);

        $this->assertEquals(3001, $exception->getCode());
        $this->assertStringContainsString('100.5', $exception->getMessage());
        $this->assertEquals(['amount' => 100.50, 'reason' => ''], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_amount_exception_with_reason(): void
    {
        $exception = PaymentValidationException::invalidAmount(100.50, 'Amount exceeds maximum');

        $this->assertEquals(3001, $exception->getCode());
        $this->assertStringContainsString('Amount exceeds maximum', $exception->getMessage());
        $this->assertEquals(['amount' => 100.50, 'reason' => 'Amount exceeds maximum'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_currency_exception(): void
    {
        $exception = PaymentValidationException::invalidCurrency('INVALID');

        $this->assertEquals(3002, $exception->getCode());
        $this->assertStringContainsString('INVALID', $exception->getMessage());
        $this->assertEquals(['currency' => 'INVALID'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_order_id_exception(): void
    {
        $exception = PaymentValidationException::invalidOrderId('ORD-123');

        $this->assertEquals(3003, $exception->getCode());
        $this->assertStringContainsString('ORD-123', $exception->getMessage());
        $this->assertEquals(['order_id' => 'ORD-123', 'reason' => ''], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_order_id_exception_with_reason(): void
    {
        $exception = PaymentValidationException::invalidOrderId('ORD-123', 'Order ID is empty');

        $this->assertEquals(3003, $exception->getCode());
        $this->assertStringContainsString('Order ID is empty', $exception->getMessage());
        $this->assertEquals(['order_id' => 'ORD-123', 'reason' => 'Order ID is empty'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_return_url_exception(): void
    {
        $exception = PaymentValidationException::invalidReturnUrl('invalid-url');

        $this->assertEquals(3004, $exception->getCode());
        $this->assertStringContainsString('return URL', $exception->getMessage());
        $this->assertEquals(['url' => 'invalid-url'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_return_url_exception_with_null(): void
    {
        $exception = PaymentValidationException::invalidReturnUrl(null);

        $this->assertEquals(3004, $exception->getCode());
        $this->assertEquals(['url' => null], $exception->getContext());
    }

    /** @test */
    public function it_creates_unsupported_payment_method_exception(): void
    {
        $exception = PaymentValidationException::unsupportedPaymentMethod('paypal', 'Redsys');

        $this->assertEquals(3005, $exception->getCode());
        $this->assertStringContainsString('paypal', $exception->getMessage());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertEquals(['payment_method' => 'paypal', 'provider' => 'Redsys'], $exception->getContext());
    }

    /** @test */
    public function it_creates_missing_required_field_exception(): void
    {
        $exception = PaymentValidationException::missingRequiredField('amount');

        $this->assertEquals(3006, $exception->getCode());
        $this->assertStringContainsString('amount', $exception->getMessage());
        $this->assertEquals(['field' => 'amount'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_email_exception(): void
    {
        $exception = PaymentValidationException::invalidEmail('invalid-email');

        $this->assertEquals(3007, $exception->getCode());
        $this->assertStringContainsString('invalid-email', $exception->getMessage());
        $this->assertEquals(['email' => 'invalid-email'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_field_length_exception(): void
    {
        $exception = PaymentValidationException::invalidFieldLength('orderId', 256, 255);

        $this->assertEquals(3008, $exception->getCode());
        $this->assertStringContainsString('orderId', $exception->getMessage());
        $this->assertStringContainsString('256', $exception->getMessage());
        $this->assertStringContainsString('255', $exception->getMessage());
        $this->assertEquals([
            'field' => 'orderId',
            'actual_length' => 256,
            'max_length' => 255,
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_validation_failed_exception(): void
    {
        $exception = PaymentValidationException::validationFailed('status', 'Status cannot be empty');

        $this->assertEquals(3009, $exception->getCode());
        $this->assertStringContainsString('status', $exception->getMessage());
        $this->assertStringContainsString('Status cannot be empty', $exception->getMessage());
        $this->assertEquals([
            'field' => 'status',
            'reason' => 'Status cannot be empty',
        ], $exception->getContext());
    }
}

