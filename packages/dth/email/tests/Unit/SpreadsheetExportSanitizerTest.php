<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Support\SpreadsheetExportSanitizer;
use PHPUnit\Framework\TestCase;

class SpreadsheetExportSanitizerTest extends TestCase
{
    public function test_formula_like_values_are_exported_as_literal_text(): void
    {
        $this->assertSame("'=SUM(A1:A2)", SpreadsheetExportSanitizer::text('=SUM(A1:A2)'));
        $this->assertSame("'+cmd", SpreadsheetExportSanitizer::text('+cmd'));
        $this->assertSame("'-10+20", SpreadsheetExportSanitizer::text('-10+20'));
        $this->assertSame("'  @payload", SpreadsheetExportSanitizer::text('  @payload'));
    }

    public function test_normal_values_are_not_changed(): void
    {
        $this->assertSame('customer@example.com', SpreadsheetExportSanitizer::text('customer@example.com'));
        $this->assertSame('DTH Campaign', SpreadsheetExportSanitizer::text('DTH Campaign'));
        $this->assertSame('', SpreadsheetExportSanitizer::text(''));
    }
}
