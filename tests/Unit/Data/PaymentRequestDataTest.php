<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Data;

use Solivellaluisaberto\PayKit\Data\PaymentRequestData;
use Solivellaluisaberto\PayKit\Enums\Currency;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentRequestDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar moneda por defecto para los tests
        config(['pay-kit.currency' => 'EUR']);
    }

    /** @test */
    public function it_can_be_created_with_valid_data(): void
    {
        $request = new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345'
        );

        $this->assertEquals(100.50, $request->amount);
        $this->assertEquals(Currency::EUR, $request->currency);
        $this->assertEquals('ORD-12345', $request->orderId);
        $this->assertIsArray($request->metadata);
        $this->assertEmpty($request->metadata);
    }

    /** @test */
    public function it_uses_default_currency_from_config_when_currency_is_null(): void
    {
        config(['pay-kit.currency' => 'USD']);

        $request = new PaymentRequestData(
            amount: 100.50,
            currency: null,
            orderId: 'ORD-12345'
        );

        $this->assertEquals(Currency::USD, $request->currency);
    }

    /** @test */
    public function it_uses_eur_as_fallback_when_config_currency_is_invalid(): void
    {
        config(['pay-kit.currency' => 'INVALID']);

        $request = new PaymentRequestData(
            amount: 100.50,
            currency: null,
            orderId: 'ORD-12345'
        );

        $this->assertEquals(Currency::EUR, $request->currency);
    }

    /** @test */
    public function it_accepts_currency_as_string(): void
    {
        $request = new PaymentRequestData(
            amount: 100.50,
            currency: 'USD',
            orderId: 'ORD-12345'
        );

        $this->assertEquals(Currency::USD, $request->currency);
    }

    /** @test */
    public function it_throws_exception_when_invalid_currency_string(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: 'INVALID',
            orderId: 'ORD-12345'
        );
    }

    /** @test */
    public function it_throws_exception_when_amount_is_zero(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 0,
            currency: Currency::EUR,
            orderId: 'ORD-12345'
        );
    }

    /** @test */
    public function it_throws_exception_when_amount_is_negative(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: -10.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345'
        );
    }

    /** @test */
    public function it_throws_exception_when_amount_exceeds_maximum(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 1000000.00,
            currency: Currency::EUR,
            orderId: 'ORD-12345'
        );
    }

    /** @test */
    public function it_throws_exception_when_order_id_is_empty(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: ''
        );
    }

    /** @test */
    public function it_throws_exception_when_order_id_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: str_repeat('A', 256)
        );
    }

    /** @test */
    public function it_accepts_valid_urls(): void
    {
        $request = new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            returnUrl: 'https://example.com/return',
            cancelUrl: 'https://example.com/cancel',
            notificationUrl: 'https://example.com/notification'
        );

        $this->assertEquals('https://example.com/return', $request->returnUrl);
        $this->assertEquals('https://example.com/cancel', $request->cancelUrl);
        $this->assertEquals('https://example.com/notification', $request->notificationUrl);
    }

    /** @test */
    public function it_throws_exception_when_return_url_is_invalid(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            returnUrl: 'not-a-valid-url'
        );
    }

    /** @test */
    public function it_throws_exception_when_cancel_url_is_invalid(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            cancelUrl: 'not-a-valid-url'
        );
    }

    /** @test */
    public function it_throws_exception_when_notification_url_is_invalid(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            notificationUrl: 'not-a-valid-url'
        );
    }

    /** @test */
    public function it_accepts_metadata(): void
    {
        $metadata = [
            'description' => 'Test payment',
            'customer_email' => 'test@example.com',
            'custom_field' => 'custom_value'
        ];

        $request = new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            metadata: $metadata
        );

        $this->assertEquals($metadata, $request->metadata);
    }

    /** @test */
    public function it_throws_exception_when_description_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            metadata: ['description' => str_repeat('A', 501)]
        );
    }

    /** @test */
    public function it_throws_exception_when_customer_email_is_invalid(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            metadata: ['customer_email' => 'invalid-email']
        );
    }

    /** @test */
    public function it_accepts_valid_customer_email(): void
    {
        $request = new PaymentRequestData(
            amount: 100.50,
            currency: Currency::EUR,
            orderId: 'ORD-12345',
            metadata: ['customer_email' => 'valid@example.com']
        );

        $this->assertEquals('valid@example.com', $request->metadata['customer_email']);
    }
}

