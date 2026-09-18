<?php

namespace Tests\Feature;

use App\Support\Modules\DthModuleRegistry;
use Dth\AccountManagement\Http\Middleware\EnsureAccountIsActive;
use Tests\TestCase;

class DthModuleRegistryTest extends TestCase
{
    public function test_it_discovers_dth_modules_from_package_manifests(): void
    {
        $registry = app(DthModuleRegistry::class);
        $definitions = $registry->definitions();

        $this->assertSame([
            'dth/email',
            'dth/marketing',
            'dth/commercial',
            'dth/crm',
            'dth/human-resource',
            'dth/account-management',
        ], array_keys($definitions));

        $this->assertSame(
            ['dth/human-resource'],
            data_get($definitions, 'dth/crm.dependencies.required'),
        );
    }

    public function test_account_middleware_is_discovered_from_module_manifest(): void
    {
        $middleware = app(DthModuleRegistry::class)->authMiddlewareClasses();

        $this->assertContains(EnsureAccountIsActive::class, $middleware);
    }
}
