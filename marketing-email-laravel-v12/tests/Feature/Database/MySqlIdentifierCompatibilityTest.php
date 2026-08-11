<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MySqlIdentifierCompatibilityTest extends TestCase
{
    public function test_service_package_product_unique_index_has_an_explicit_mysql_safe_name(): void
    {
        $path = database_path('migrations/2026_08_10_190200_add_service_products_and_mixed_quote_items.php');
        $this->assertFileExists($path);

        $source = File::get($path);
        $pattern = "/unique\\(\\s*\\[\\s*'service_package_id'\\s*,\\s*'service_product_id'\\s*\\]\\s*(?:,\\s*'([^']+)')?\\s*\\)/s";

        $this->assertSame(1, preg_match($pattern, $source, $matches), 'Expected service_package_products composite unique index was not found.');

        $explicitName = $matches[1] ?? '';
        $this->assertNotSame('', $explicitName, 'Composite unique index must have an explicit short name; Laravel default exceeds MySQL 64-character identifier limit.');
        $this->assertLessThanOrEqual(64, strlen($explicitName), 'MySQL identifier names must be at most 64 characters.');
    }
}
