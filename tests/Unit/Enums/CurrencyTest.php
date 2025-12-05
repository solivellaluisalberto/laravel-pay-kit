<?php

namespace Solivellaluisaberto\PayKit\Tests\Unit\Enums;

use Solivellaluisaberto\PayKit\Enums\Currency;
use Solivellaluisaberto\PayKit\Tests\TestCase;

class CurrencyTest extends TestCase
{
    /** @test */
    public function it_returns_correct_iso4217_code_for_eur(): void
    {
        $this->assertEquals('978', Currency::EUR->getISO4217());
    }

    /** @test */
    public function it_returns_correct_iso4217_code_for_usd(): void
    {
        $this->assertEquals('840', Currency::USD->getISO4217());
    }

    /** @test */
    public function it_returns_correct_name_for_eur(): void
    {
        $this->assertEquals('Euro', Currency::EUR->getName());
    }

    /** @test */
    public function it_returns_correct_name_for_usd(): void
    {
        $this->assertEquals('Dólar estadounidense', Currency::USD->getName());
    }

    /** @test */
    public function it_can_be_created_from_valid_string(): void
    {
        $currency = Currency::tryFromString('EUR');
        
        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertEquals(Currency::EUR, $currency);
    }

    /** @test */
    public function it_returns_null_for_invalid_string(): void
    {
        $currency = Currency::tryFromString('INVALID');
        
        $this->assertNull($currency);
    }

    /** @test */
    public function it_is_case_insensitive(): void
    {
        $currency1 = Currency::tryFromString('eur');
        $currency2 = Currency::tryFromString('EUR');
        $currency3 = Currency::tryFromString('Eur');
        
        $this->assertEquals(Currency::EUR, $currency1);
        $this->assertEquals(Currency::EUR, $currency2);
        $this->assertEquals(Currency::EUR, $currency3);
    }

    /** @test */
    public function it_has_value_for_all_currencies(): void
    {
        foreach (Currency::cases() as $currency) {
            $this->assertNotEmpty($currency->value);
            $this->assertNotEmpty($currency->getISO4217());
            $this->assertNotEmpty($currency->getName());
        }
    }
}

