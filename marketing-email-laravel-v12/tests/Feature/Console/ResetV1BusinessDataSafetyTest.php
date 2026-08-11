<?php

namespace Tests\Feature\Console;

use Database\Seeders\V1AcceptanceTestSeeder;
use RuntimeException;
use Tests\TestCase;

class ResetV1BusinessDataSafetyTest extends TestCase
{
    public function test_business_reset_is_blocked_in_production_even_with_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('v1:reset-business-data', ['--force' => true])
            ->expectsOutput('Lệnh reset dữ liệu V1 bị vô hiệu hóa trên môi trường production.')
            ->assertExitCode(1);
    }

    public function test_v1_acceptance_seeder_is_blocked_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('V1AcceptanceTestSeeder chỉ được phép chạy ngoài production.');

        (new V1AcceptanceTestSeeder())->run();
    }
}
