<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Exceptions;

use Solivellaluisaberto\PayKit\Enums\PaymentProvider;
use Solivellaluisaberto\PayKit\Exceptions\PaymentException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentProviderException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentProviderExceptionTest extends TestCase
{
    /** @test */
    public function it_extends_payment_exception(): void
    {
        $exception = PaymentProviderException::apiError(PaymentProvider::REDSYS, 'Test error');

        $this->assertInstanceOf(PaymentException::class, $exception);
    }

    /** @test */
    public function it_creates_api_error_exception(): void
    {
        $exception = PaymentProviderException::apiError(PaymentProvider::REDSYS, 'Connection failed');

        $this->assertEquals(2001, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('Connection failed', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'provider_error_code' => null,
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_api_error_exception_with_error_code(): void
    {
        $exception = PaymentProviderException::apiError(
            PaymentProvider::REDSYS,
            'Connection failed',
            'ERR_001'
        );

        $this->assertEquals(2001, $exception->getCode());
        $this->assertEquals([
            'provider' => 'redsys',
            'provider_error_code' => 'ERR_001',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_api_error_exception_with_previous_exception(): void
    {
        $previous = new \Exception('Previous error');
        $exception = PaymentProviderException::apiError(
            PaymentProvider::REDSYS,
            'Connection failed',
            null,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    /** @test */
    public function it_creates_connection_error_exception(): void
    {
        $exception = PaymentProviderException::connectionError(PaymentProvider::STRIPE);

        $this->assertEquals(2002, $exception->getCode());
        $this->assertStringContainsString('Stripe', $exception->getMessage());
        $this->assertEquals(['provider' => 'stripe'], $exception->getContext());
    }

    /** @test */
    public function it_creates_connection_error_exception_with_previous(): void
    {
        $previous = new \Exception('Network error');
        $exception = PaymentProviderException::connectionError(PaymentProvider::STRIPE, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    /** @test */
    public function it_creates_timeout_exception(): void
    {
        $exception = PaymentProviderException::timeout(PaymentProvider::PAYPAL);

        $this->assertEquals(2003, $exception->getCode());
        $this->assertStringContainsString('PayPal', $exception->getMessage());
        $this->assertEquals(['provider' => 'paypal'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_response_exception(): void
    {
        $exception = PaymentProviderException::invalidResponse(PaymentProvider::REDSYS);

        $this->assertEquals(2004, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'reason' => '',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_response_exception_with_reason(): void
    {
        $exception = PaymentProviderException::invalidResponse(
            PaymentProvider::REDSYS,
            'Missing required fields'
        );

        $this->assertEquals(2004, $exception->getCode());
        $this->assertStringContainsString('Missing required fields', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'reason' => 'Missing required fields',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_payment_declined_exception(): void
    {
        $exception = PaymentProviderException::paymentDeclined(
            PaymentProvider::REDSYS,
            'Insufficient funds'
        );

        $this->assertEquals(2005, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('Insufficient funds', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'reason' => 'Insufficient funds',
            'decline_code' => null,
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_payment_declined_exception_with_decline_code(): void
    {
        $exception = PaymentProviderException::paymentDeclined(
            PaymentProvider::REDSYS,
            'Insufficient funds',
            'DEC_001'
        );

        $this->assertEquals(2005, $exception->getCode());
        $this->assertEquals([
            'provider' => 'redsys',
            'reason' => 'Insufficient funds',
            'decline_code' => 'DEC_001',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_signature_verification_failed_exception(): void
    {
        $exception = PaymentProviderException::signatureVerificationFailed(PaymentProvider::REDSYS);

        $this->assertEquals(2006, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('Signature verification failed', $exception->getMessage());
        $this->assertEquals(['provider' => 'redsys'], $exception->getContext());
    }

    /** @test */
    public function it_creates_payment_not_found_exception(): void
    {
        $exception = PaymentProviderException::paymentNotFound(PaymentProvider::STRIPE, 'pay_123');

        $this->assertEquals(2007, $exception->getCode());
        $this->assertStringContainsString('Stripe', $exception->getMessage());
        $this->assertStringContainsString('pay_123', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'stripe',
            'payment_id' => 'pay_123',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_refund_not_available_exception(): void
    {
        $exception = PaymentProviderException::refundNotAvailable(
            PaymentProvider::REDSYS,
            'Refund period expired'
        );

        $this->assertEquals(2008, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('Refund period expired', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'reason' => 'Refund period expired',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_operation_not_supported_exception(): void
    {
        $exception = PaymentProviderException::operationNotSupported(
            PaymentProvider::REDSYS,
            'partial_refund'
        );

        $this->assertEquals(2009, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('partial_refund', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'redsys',
            'operation' => 'partial_refund',
        ], $exception->getContext());
    }

    /** @test */
    public function it_returns_correct_http_status_code_for_payment_declined(): void
    {
        $exception = PaymentProviderException::paymentDeclined(
            PaymentProvider::REDSYS,
            'Insufficient funds'
        );

        $this->assertEquals(402, $exception->getHttpStatusCode());
    }

    /** @test */
    public function it_returns_correct_http_status_code_for_payment_not_found(): void
    {
        $exception = PaymentProviderException::paymentNotFound(PaymentProvider::STRIPE, 'pay_123');

        $this->assertEquals(404, $exception->getHttpStatusCode());
    }

    /** @test */
    public function it_returns_default_http_status_code_for_other_errors(): void
    {
        $exception = PaymentProviderException::apiError(PaymentProvider::REDSYS, 'Test error');

        $this->assertEquals(502, $exception->getHttpStatusCode());
    }
}

