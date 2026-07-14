<?php

declare(strict_types=1);

namespace Kilden\Laravel\Tests;

use Kilden\Laravel\KildenServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [KildenServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('kilden.write_key', 'sk_test_secret');
        $app['config']->set('kilden.enabled', false); // no network in tests
        $app['config']->set('kilden.identity.secret', 'test-identity-secret');
        $app['config']->set('kilden.identity.kid', 'k1');
        $app['config']->set('kilden.identity.middleware', ['web', 'auth']);
    }
}
