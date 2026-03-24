<?php

namespace Juanparati\QueryTimeout\Test;

use Juanparati\QueryTimeout\Providers\QueryTimeoutProvider;
use Orchestra\Testbench\TestCase;

/**
 * Class TimeoutTestBase.
 */
abstract class TimeoutTestBase extends TestCase
{
    /**
     * Load service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [QueryTimeoutProvider::class];
    }

    /**
     * Set connection before each test.
     *
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'default');
        config()->set('database.connections.default', [
            'driver' => env('DB_DRIVER', 'mariadb'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '33060'),
            'username' => env('DB_USERNAME', 'homestead'),
            'password' => env('DB_PASSWORD', 'secret'),
            'database' => env('DB_DATABASE', ''),
            'prefix' => '',
        ]);
    }
}
