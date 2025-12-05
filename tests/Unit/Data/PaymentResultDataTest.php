<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Data;

use Solivellaluisaberto\PayKit\Data\PaymentResultData;
use Solivellaluisaberto\PayKit\Exceptions\PaymentValidationException;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class PaymentResultDataTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_successful_payment(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: 'pay_1234567890',
            transactionId: 'txn_1234567890',
            message: 'Payment processed successfully',
            data: ['order_id' => 'ORD-123']
        );

        $this->assertTrue($result->success);
        $this->assertEquals('completed', $result->status);
        $this->assertEquals('pay_1234567890', $result->paymentId);
        $this->assertEquals('txn_1234567890', $result->transactionId);
        $this->assertEquals('Payment processed successfully', $result->message);
        $this->assertEquals(['order_id' => 'ORD-123'], $result->data);
    }

    /** @test */
    public function it_can_be_created_with_failed_payment(): void
    {
        $result = new PaymentResultData(
            success: false,
            status: 'failed',
            message: 'Payment failed'
        );

        $this->assertFalse($result->success);
        $this->assertEquals('failed', $result->status);
        $this->assertNull($result->paymentId);
        $this->assertNull($result->transactionId);
        $this->assertEquals('Payment failed', $result->message);
        $this->assertIsArray($result->data);
        $this->assertEmpty($result->data);
    }

    /** @test */
    public function it_throws_exception_when_status_is_empty(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: '',
            paymentId: 'pay_123'
        );
    }

    /** @test */
    public function it_throws_exception_when_status_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: str_repeat('A', 51),
            paymentId: 'pay_123'
        );
    }

    /** @test */
    public function it_throws_exception_when_status_has_invalid_characters(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: 'invalid status!',
            paymentId: 'pay_123'
        );
    }

    /** @test */
    public function it_throws_exception_when_success_is_true_but_no_identifiers(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: 'completed'
        );
    }

    /** @test */
    public function it_accepts_success_true_with_payment_id_only(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: 'pay_123'
        );

        $this->assertTrue($result->success);
        $this->assertEquals('pay_123', $result->paymentId);
        $this->assertNull($result->transactionId);
    }

    /** @test */
    public function it_accepts_success_true_with_transaction_id_only(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            transactionId: 'txn_123'
        );

        $this->assertTrue($result->success);
        $this->assertNull($result->paymentId);
        $this->assertEquals('txn_123', $result->transactionId);
    }

    /** @test */
    public function it_throws_exception_when_payment_id_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: str_repeat('A', 256)
        );
    }

    /** @test */
    public function it_throws_exception_when_transaction_id_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: 'completed',
            transactionId: str_repeat('A', 256)
        );
    }

    /** @test */
    public function it_throws_exception_when_message_exceeds_max_length(): void
    {
        $this->expectException(PaymentValidationException::class);

        new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: 'pay_123',
            message: str_repeat('A', 1001)
        );
    }

    /** @test */
    public function it_accepts_valid_statuses(): void
    {
        $statuses = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];

        foreach ($statuses as $status) {
            $result = new PaymentResultData(
                success: true,
                status: $status,
                paymentId: 'pay_123'
            );

            $this->assertEquals($status, $result->status);
        }
    }

    /** @test */
    public function it_accepts_status_with_hyphens_and_underscores(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'requires-action',
            paymentId: 'pay_123'
        );

        $this->assertEquals('requires-action', $result->status);
    }

    /** @test */
    public function it_accepts_status_with_numbers(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'status123',
            paymentId: 'pay_123'
        );

        $this->assertEquals('status123', $result->status);
    }

    /** @test */
    public function it_is_completed_returns_true_for_completed_status(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: 'pay_123'
        );

        $this->assertTrue($result->isCompleted());
    }

    /** @test */
    public function it_is_completed_returns_true_for_authorized_status(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'authorized',
            paymentId: 'pay_123'
        );

        $this->assertTrue($result->isCompleted());
    }

    /** @test */
    public function it_is_pending_returns_true_for_pending_status(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'pending',
            paymentId: 'pay_123'
        );

        $this->assertTrue($result->isPending());
    }

    /** @test */
    public function it_is_failed_returns_true_for_failed_status(): void
    {
        $result = new PaymentResultData(
            success: false,
            status: 'failed'
        );

        $this->assertTrue($result->isFailed());
    }

    /** @test */
    public function it_requires_action_returns_true_for_requires_action_status(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'requires_action',
            paymentId: 'pay_123'
        );

        $this->assertTrue($result->requiresAction());
    }

    /** @test */
    public function it_get_identifier_returns_payment_id_when_available(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            paymentId: 'pay_123',
            transactionId: 'txn_456'
        );

        $this->assertEquals('pay_123', $result->getIdentifier());
    }

    /** @test */
    public function it_get_identifier_returns_transaction_id_when_payment_id_is_null(): void
    {
        $result = new PaymentResultData(
            success: true,
            status: 'completed',
            transactionId: 'txn_456'
        );

        $this->assertEquals('txn_456', $result->getIdentifier());
    }

    /** @test */
    public function it_get_identifier_returns_null_when_both_are_null(): void
    {
        $result = new PaymentResultData(
            success: false,
            status: 'failed'
        );

        $this->assertNull($result->getIdentifier());
    }
}

