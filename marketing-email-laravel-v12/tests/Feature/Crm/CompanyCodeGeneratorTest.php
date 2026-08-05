<?php

namespace Tests\Feature\Crm;

use App\Services\Crm\CompanyCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_codes_increase_without_using_company_count(): void
    {
        $first = app(CompanyCodeGenerator::class)->next();
        $second = app(CompanyCodeGenerator::class)->next();

        $this->assertNotSame($first, $second);
        $this->assertStringEndsWith('000001', $first);
        $this->assertStringEndsWith('000002', $second);
    }
}
