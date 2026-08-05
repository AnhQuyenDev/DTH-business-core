<?php

namespace Tests\Feature\Crm;

use App\Models\Crm\Company;
use App\Services\Crm\CompanyResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(array $overrides = []): Company
    {
        return Company::query()->create(array_merge([
            'company_code' => 'COM-2026-000001',
            'legal_name' => 'Công ty TNHH ABC',
            'normalized_name' => 'abc',
            'tax_code' => '0312345678',
            'email_domain' => 'abc.com.vn',
        ], $overrides));
    }

    public function test_tax_code_exact_match_is_auto_match(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'tax_code' => '0312345678',
        ]);

        $this->assertTrue($result->found());
        $this->assertTrue($result->isAutoMatch());
        $this->assertSame(100, $result->confidenceScore);
        $this->assertSame('tax_code', $result->matchedBy);
    }

    public function test_tax_code_formatting_ignores_non_digits(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'tax_code' => '0312-3456-78',
        ]);

        $this->assertTrue($result->found());
        $this->assertSame(100, $result->confidenceScore);
    }

    public function test_email_domain_single_match_is_auto_match(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'business_email' => 'sales@abc.com.vn',
        ]);

        $this->assertTrue($result->found());
        $this->assertTrue($result->isAutoMatch());
        $this->assertSame(85, $result->confidenceScore);
        $this->assertSame('email_domain', $result->matchedBy);
    }

    public function test_free_email_domain_is_ignored(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'business_email' => 'someone@gmail.com',
        ]);

        $this->assertFalse($result->found());
    }

    public function test_normalized_name_match_is_candidate_not_auto_match(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'company_name' => 'Công ty TNHH ABC',
        ]);

        $this->assertTrue($result->found());
        $this->assertFalse($result->isAutoMatch());
        $this->assertSame(65, $result->confidenceScore);
        $this->assertSame('normalized_name', $result->matchedBy);
    }

    public function test_no_match_returns_not_found(): void
    {
        $result = app(CompanyResolutionService::class)->resolve([
            'company_name' => 'Công ty XYZ',
        ]);

        $this->assertFalse($result->found());
        $this->assertNull($result->company);
    }

    public function test_domain_match_skipped_when_tax_code_conflicts(): void
    {
        $this->makeCompany();

        $result = app(CompanyResolutionService::class)->resolve([
            'business_email' => 'sales@abc.com.vn',
            'tax_code' => '0999999999',
        ]);

        $this->assertFalse($result->found());
        $this->assertNull($result->company);
    }
}
