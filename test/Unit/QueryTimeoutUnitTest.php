<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Juanparati\QueryTimeout\QueryTimeout;
use Juanparati\QueryTimeout\QueryTimeoutBuilder;
use PHPUnit\Framework\TestCase;

class QueryTimeoutUnitTest extends TestCase
{
    private function createService(array $config = []): QueryTimeout
    {
        return new QueryTimeout(array_merge([
            'default_timeout' => 30,
            'resolution' => 'millisecond',
        ], $config));
    }

    public function test_initial_result_is_null(): void
    {
        $this->assertNull($this->createService()->getResult());
    }

    public function test_initial_query_time_is_null(): void
    {
        $this->assertNull($this->createService()->getQueryTime());
    }

    public function test_build_returns_builder_instance(): void
    {
        $this->assertInstanceOf(QueryTimeoutBuilder::class, $this->createService()->build());
    }

    public function test_time_resolution_microsecond(): void
    {
        $this->assertTimeResolutionScale(1, 'microsecond');
    }

    public function test_time_resolution_microseconds_plural(): void
    {
        $this->assertTimeResolutionScale(1, 'microseconds');
    }

    public function test_time_resolution_millisecond(): void
    {
        $this->assertTimeResolutionScale(1e3, 'millisecond');
    }

    public function test_time_resolution_milliseconds_plural(): void
    {
        $this->assertTimeResolutionScale(1e3, 'milliseconds');
    }

    public function test_time_resolution_second(): void
    {
        $this->assertTimeResolutionScale(1e6, 'second');
    }

    public function test_time_resolution_unknown_defaults_to_seconds(): void
    {
        $this->assertTimeResolutionScale(1e6, 'unknown');
    }

    private function assertTimeResolutionScale(float|int $expected, string $resolution): void
    {
        $service = $this->createService(['resolution' => $resolution]);
        $method = new \ReflectionMethod($service, 'getTimeResolutionScale');

        $this->assertEquals($expected, $method->invoke($service));
    }
}
