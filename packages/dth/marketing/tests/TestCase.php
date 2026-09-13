<?php

namespace Dth\Marketing\Tests;

use Dth\Marketing\MarketingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MarketingServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('m', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('dth-marketing.enabled', true);
        $app['config']->set('dth-marketing.features.public_submission', true);
        $app['config']->set('dth-marketing.features.audiences', true);
        $app['config']->set('dth-marketing.features.segments', true);
        $app['config']->set('dth-marketing.features.utm', true);
        $app['config']->set('dth-marketing.features.email_bridge', true);
        $app['config']->set('dth-marketing.features.analytics', true);
        $app['config']->set('dth-marketing.authorization.mode', 'off');
        $app['config']->set('dth-marketing.security.submission_rate_limit_per_minute', 100);
        $app['config']->set('dth-marketing.security.submission_rate_limit_per_hour', 1000);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testing',
            '--force' => true,
        ])->run();
    }
}
