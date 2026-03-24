<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Juanparati\QueryTimeout\Providers\QueryTimeoutProvider;
use Juanparati\QueryTimeout\QueryTimeout;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [QueryTimeoutProvider::class];
    }

    public function test_config_is_merged(): void
    {
        $config = config('query-timeout');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('default_timeout', $config);
        $this->assertArrayHasKey('resolution', $config);
        $this->assertArrayHasKey('mysql', $config);
        $this->assertArrayHasKey('mariadb', $config);
        $this->assertArrayHasKey('pgsql', $config);
    }

    public function test_default_resolution_is_millisecond(): void
    {
        $this->assertEquals('millisecond', config('query-timeout.resolution'));
    }
    
    public function test_default_timeout_is_derived_from_max_execution_time(): void
    {
        $expected = (ini_get('max_execution_time') ?: 100) - 10;

        $this->assertEquals($expected, config('query-timeout.default_timeout'));
    }

    public function test_query_timeout_is_registered_as_singleton(): void
    {
        $instance1 = app(QueryTimeout::class);
        $instance2 = app(QueryTimeout::class);

        $this->assertSame($instance1, $instance2);
    }

    public function test_query_timeout_resolves_to_correct_class(): void
    {
        $this->assertInstanceOf(QueryTimeout::class, app(QueryTimeout::class));
    }

    public function test_config_can_be_overridden(): void
    {
        config()->set('query-timeout.resolution', 'microsecond');

        $this->assertEquals('microsecond', config('query-timeout.resolution'));
    }
}
