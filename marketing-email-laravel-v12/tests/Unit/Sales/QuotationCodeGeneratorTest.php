<?php

namespace Tests\Unit\Sales;

use App\Services\Sales\QuotationCodeGenerator;
use PHPUnit\Framework\TestCase;

class QuotationCodeGeneratorTest extends TestCase
{
    private QuotationCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new QuotationCodeGenerator;
    }

    public function test_public_token_is_hex_string(): void
    {
        $token = $this->generator->generatePublicToken();

        $this->assertTrue(ctype_xdigit($token));
        $this->assertEquals(64, strlen($token));
    }

    public function test_public_token_is_random(): void
    {
        $token1 = $this->generator->generatePublicToken();
        $token2 = $this->generator->generatePublicToken();

        $this->assertNotEquals($token1, $token2);
    }
}
