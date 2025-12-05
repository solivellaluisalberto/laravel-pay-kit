<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Exceptions;

use Solivellaluisaberto\PayKit\Exceptions\PaymentConfigurationException;
use Solivellaluisaberto\PayKit\Exceptions\PaymentException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentConfigurationExceptionTest extends TestCase
{
    /** @test */
    public function it_extends_payment_exception(): void
    {
        $exception = PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');

        $this->assertInstanceOf(PaymentException::class, $exception);
    }

    /** @test */
    public function it_has_correct_http_status_code(): void
    {
        $exception = PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');

        $this->assertEquals(500, $exception->getHttpStatusCode());
    }

    /** @test */
    public function it_creates_missing_credentials_exception(): void
    {
        $exception = PaymentConfigurationException::missingCredentials('Redsys', 'merchant_code');

        $this->assertEquals(1001, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('merchant_code', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'Redsys',
            'credential' => 'merchant_code',
            'config_key' => 'payments.Redsys.merchant_code',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_api_key_exception(): void
    {
        $exception = PaymentConfigurationException::invalidApiKey('Stripe');

        $this->assertEquals(1002, $exception->getCode());
        $this->assertStringContainsString('Stripe', $exception->getMessage());
        $this->assertEquals(['provider' => 'Stripe'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_environment_exception(): void
    {
        $exception = PaymentConfigurationException::invalidEnvironment('Redsys', 'invalid-env');

        $this->assertEquals(1003, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('invalid-env', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'Redsys',
            'environment' => 'invalid-env',
        ], $exception->getContext());
    }

    /** @test */
    public function it_creates_unsupported_provider_exception(): void
    {
        $exception = PaymentConfigurationException::unsupportedProvider('UnknownProvider');

        $this->assertEquals(1004, $exception->getCode());
        $this->assertStringContainsString('UnknownProvider', $exception->getMessage());
        $this->assertEquals(['provider' => 'UnknownProvider'], $exception->getContext());
    }

    /** @test */
    public function it_creates_invalid_configuration_exception(): void
    {
        $exception = PaymentConfigurationException::invalidConfiguration('Redsys', 'Terminal is required');

        $this->assertEquals(1005, $exception->getCode());
        $this->assertStringContainsString('Redsys', $exception->getMessage());
        $this->assertStringContainsString('Terminal is required', $exception->getMessage());
        $this->assertEquals([
            'provider' => 'Redsys',
            'reason' => 'Terminal is required',
        ], $exception->getContext());
    }
}

