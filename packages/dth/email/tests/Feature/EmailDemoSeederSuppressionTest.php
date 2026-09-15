<?php

namespace Dth\Email\Tests\Feature;

use Database\Seeders\EmailDemoSeeder;
use Dth\Email\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class EmailDemoSeederSuppressionTest extends TestCase
{
    public function test_demo_seeder_creates_suppression_records(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@dth.local'],
            [
                'name' => 'DTH Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        (new EmailDemoSeeder())->run();

        $this->assertDatabaseHas('email_suppressions', [
            'email' => 'unsubscribe@demo.local',
            'reason' => 'unsubscribe',
        ]);
        $this->assertDatabaseHas('email_suppressions', [
            'email' => 'bounce@demo.local',
            'reason' => 'bounce',
        ]);
        $this->assertGreaterThanOrEqual(2, DB::table('email_suppressions')->count());
        $this->assertDatabaseHas('email_suppressions', [
            'email' => 'manual@demo.local',
            'reason' => 'manual',
        ]);
    }
}
