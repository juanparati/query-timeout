<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Juanparati\QueryTimeout\QueryTimeout;
use Juanparati\QueryTimeout\QueryTimeoutBuilder;
use Juanparati\QueryTimeout\Test\TimeoutTestBase;

class QueryTimeoutBuilderTest extends TimeoutTestBase
{
    private function createBuilder(): QueryTimeoutBuilder
    {
        return new QueryTimeoutBuilder(
            new QueryTimeout(['default_timeout' => 30, 'resolution' => 'millisecond'])
        );
    }

    public function test_timeout_returns_self_for_fluent_chaining(): void
    {
        $builder = $this->createBuilder();

        $this->assertSame($builder, $builder->timeout(5));
    }

    public function test_on_returns_self_for_fluent_chaining(): void
    {
        $builder = $this->createBuilder();

        $this->assertSame($builder, $builder->on('mysql'));
    }

    public function test_for_returns_self_for_fluent_chaining(): void
    {
        $builder = $this->createBuilder();

        $this->assertSame($builder, $builder->for(fn () => null));
    }

    public function test_full_fluent_chain_returns_builder(): void
    {
        $result = $this->createBuilder()
            ->timeout(5)
            ->on('default')
            ->for(fn () => true);

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $result);
    }

    public function test_builder_resolves_service_from_container(): void
    {
        $builder = new QueryTimeoutBuilder;

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $builder);
    }

    public function test_invoke_without_callback_returns_builder(): void
    {
        $service = app(QueryTimeout::class);

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $service());
    }

    public function test_build_returns_new_builder_instance(): void
    {
        $service = app(QueryTimeout::class);

        $builder1 = $service->build();
        $builder2 = $service->build();

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $builder1);
        $this->assertNotSame($builder1, $builder2);
    }

    public function test_when_timeout_returns_self_for_fluent_chaining(): void
    {
        $builder = $this->createBuilder();

        $this->assertSame($builder, $builder->whenTimeout(fn () => null));
    }

    public function test_default_returns_self_for_fluent_chaining(): void
    {
        $builder = $this->createBuilder();

        $this->assertSame($builder, $builder->default('fallback'));
    }

    public function test_run_without_callback_throws_runtime_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Query callback is not set.');

        $this->createBuilder()->timeout(5)->run();
    }

    public function test_full_fluent_chain_with_when_timeout_returns_builder(): void
    {
        $result = $this->createBuilder()
            ->timeout(5)
            ->on('default')
            ->whenTimeout(fn () => null)
            ->for(fn () => true);

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $result);
    }

    public function test_full_fluent_chain_with_default_and_when_timeout(): void
    {
        $result = $this->createBuilder()
            ->timeout(5)
            ->on('default')
            ->whenTimeout(fn () => null)
            ->default('fallback')
            ->for(fn () => true);

        $this->assertInstanceOf(QueryTimeoutBuilder::class, $result);
    }
}
