<?php

namespace Dth\Marketing\Tests\Unit;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Dth\Marketing\Services\SemanticFieldResolver;
use Dth\Marketing\Tests\TestCase;

class SemanticFieldResolverTest extends TestCase
{
    public function test_it_uses_human_facing_signals_not_only_field_keys(): void
    {
        $resolver = app(SemanticFieldResolver::class);

        $name = $resolver->resolve(
            fieldKey: 'abc_xyz',
            label: 'Họ và Tên',
            placeholder: 'Nhập họ và tên...',
            fieldType: FormFieldType::Text,
            audienceType: FormAudienceType::Personal,
        );
        $this->assertSame(SemanticFieldRole::PersonName, $name['role']);
        $this->assertGreaterThanOrEqual(SemanticFieldResolver::AUTO_THRESHOLD, $name['confidence']);

        $company = $resolver->resolve(
            fieldKey: 'field_001',
            label: 'Tên Doanh Nghiệp',
            fieldType: FormFieldType::Text,
            audienceType: FormAudienceType::Business,
        );
        $this->assertSame(SemanticFieldRole::CompanyName, $company['role']);

        $phone = $resolver->resolve(
            fieldKey: 'whatever',
            label: 'Liên hệ',
            fieldType: FormFieldType::Phone,
            audienceType: FormAudienceType::Personal,
        );
        $this->assertSame(SemanticFieldRole::ContactPhone, $phone['role']);
        $this->assertSame(100, $phone['confidence']);
    }
}
