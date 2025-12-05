<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Data;

use Solivellaluisaberto\PayKit\Data\PaymentResponseData;
use Solivellaluisaberto\PayKit\Enums\PaymentType;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentResponseDataTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_redirect_type(): void
    {
        $response = new PaymentResponseData(
            type: PaymentType::REDIRECT,
            data: ['order_id' => 'ORD-123'],
            redirectUrl: 'https://example.com/redirect'
        );

        $this->assertEquals(PaymentType::REDIRECT, $response->type);
        $this->assertEquals('https://example.com/redirect', $response->redirectUrl);
        $this->assertNull($response->clientSecret);
        $this->assertNull($response->formHtml);
        $this->assertEquals(['order_id' => 'ORD-123'], $response->data);
    }

    /** @test */
    public function it_can_be_created_with_api_type(): void
    {
        $response = new PaymentResponseData(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123'],
            clientSecret: 'pi_123_secret_abc'
        );

        $this->assertEquals(PaymentType::API, $response->type);
        $this->assertEquals('pi_123_secret_abc', $response->clientSecret);
        $this->assertNull($response->redirectUrl);
        $this->assertNull($response->formHtml);
        $this->assertEquals(['payment_intent_id' => 'pi_123'], $response->data);
    }

    /** @test */
    public function it_can_be_created_with_form_type(): void
    {
        $formHtml = '<form action="https://example.com" method="POST">...</form>';

        $response = new PaymentResponseData(
            type: PaymentType::FORM,
            data: ['order_id' => 'ORD-123'],
            formHtml: $formHtml
        );

        $this->assertEquals(PaymentType::FORM, $response->type);
        $this->assertEquals($formHtml, $response->formHtml);
        $this->assertNull($response->redirectUrl);
        $this->assertNull($response->clientSecret);
        $this->assertEquals(['order_id' => 'ORD-123'], $response->data);
    }

    /** @test */
    public function it_throws_exception_when_data_is_empty(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::REDIRECT,
            data: [],
            redirectUrl: 'https://example.com/redirect'
        );
    }

    /** @test */
    public function it_throws_exception_when_redirect_type_has_no_redirect_url(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::REDIRECT,
            data: ['order_id' => 'ORD-123']
        );
    }

    /** @test */
    public function it_throws_exception_when_redirect_type_has_invalid_url(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::REDIRECT,
            data: ['order_id' => 'ORD-123'],
            redirectUrl: 'not-a-valid-url'
        );
    }

    /** @test */
    public function it_throws_exception_when_api_type_has_no_client_secret(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123']
        );
    }

    /** @test */
    public function it_throws_exception_when_api_type_has_empty_client_secret(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123'],
            clientSecret: ''
        );
    }

    /** @test */
    public function it_throws_exception_when_form_type_has_no_form_html(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::FORM,
            data: ['order_id' => 'ORD-123']
        );
    }

    /** @test */
    public function it_throws_exception_when_form_type_has_empty_form_html(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResponseData(
            type: PaymentType::FORM,
            data: ['order_id' => 'ORD-123'],
            formHtml: ''
        );
    }

    /** @test */
    public function it_accepts_optional_fields_for_redirect_type(): void
    {
        $response = new PaymentResponseData(
            type: PaymentType::REDIRECT,
            data: ['order_id' => 'ORD-123'],
            redirectUrl: 'https://example.com/redirect',
            clientSecret: null,
            formHtml: null
        );

        $this->assertEquals(PaymentType::REDIRECT, $response->type);
        $this->assertEquals('https://example.com/redirect', $response->redirectUrl);
    }

    /** @test */
    public function it_accepts_optional_fields_for_api_type(): void
    {
        $response = new PaymentResponseData(
            type: PaymentType::API,
            data: ['payment_intent_id' => 'pi_123'],
            redirectUrl: null,
            clientSecret: 'pi_123_secret_abc',
            formHtml: null
        );

        $this->assertEquals(PaymentType::API, $response->type);
        $this->assertEquals('pi_123_secret_abc', $response->clientSecret);
    }

    /** @test */
    public function it_accepts_optional_fields_for_form_type(): void
    {
        $formHtml = '<form action="https://example.com" method="POST">...</form>';

        $response = new PaymentResponseData(
            type: PaymentType::FORM,
            data: ['order_id' => 'ORD-123'],
            redirectUrl: null,
            clientSecret: null,
            formHtml: $formHtml
        );

        $this->assertEquals(PaymentType::FORM, $response->type);
        $this->assertEquals($formHtml, $response->formHtml);
    }
}

