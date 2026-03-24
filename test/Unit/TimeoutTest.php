<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Illuminate\Database\QueryException;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;
use Juanparati\QueryTimeout\QueryTimeout;
use Juanparati\QueryTimeout\Test\TimeoutTestBase;

class TimeoutTest extends TimeoutTestBase
{
    public function test_timeout()
    {
        $this->assertThrows(
            fn () => app(QueryTimeout::class)->run(fn () => static::generateSleepQuery(3), 2),
            QueryTimeoutException::class
        );

        // This one should not raise any error, because max_statement_time/max_execution_time was restored.
        $this->assertDoesntThrow(fn () => static::generateSleepQuery(3));

    }

    public function test_without_timeout()
    {
        $error = null;

        try {
            app(QueryTimeout::class)->run(fn () => \DB::select('SELECT TRUE'), 3);
        } catch (QueryException $error) {
        }

        $this->assertNull($error);
    }

    public function test_runtime()
    {
        config()->set('query-timeout.resolution', 'second');

        $this->assertGreaterThan(
            app(QueryTimeout::class)->run(fn () => static::generateSleepQuery(1), 2)
                ->getQueryTime(),
            2
        );

        config()->set('query-timeout.resolution', 'millisecond');

        $this->assertGreaterThan(
            app(QueryTimeout::class)->run(fn () => static::generateSleepQuery(1), 2)
                ->getQueryTime(),
            1200,
        );
    }

    public function test_when_timeout_callback_is_called_on_timeout()
    {
        $called = false;

        $this->assertThrows(function () use (&$called) {
            app(QueryTimeout::class)->run(
                fn () => static::generateSleepQuery(3),
                2,
                null,
                function (QueryTimeoutException $e) use (&$called) {
                    $called = true;
                }
            );
        }, QueryTimeoutException::class);

        $this->assertTrue($called, 'whenTimeout callback should have been called');
    }

    public function test_when_timeout_callback_receives_exception_instance()
    {
        $receivedException = null;

        $this->assertThrows(function () use (&$receivedException) {
            app(QueryTimeout::class)->run(
                fn () => static::generateSleepQuery(3),
                2,
                null,
                function (QueryTimeoutException $e) use (&$receivedException) {
                    $receivedException = $e;
                }
            );
        }, QueryTimeoutException::class);

        $this->assertInstanceOf(QueryTimeoutException::class, $receivedException);
        $this->assertEquals('default', $receivedException->getConnectionName());
    }

    public function test_when_timeout_callback_is_not_called_without_timeout()
    {
        $called = false;

        app(QueryTimeout::class)->run(
            fn () => \DB::select('SELECT TRUE'),
            3,
            null,
            function () use (&$called) {
                $called = true;
            }
        );

        $this->assertFalse($called, 'whenTimeout callback should not have been called');
    }

    public function test_when_timeout_with_no_throw_calls_callback_and_returns_fallback()
    {
        $called = false;

        $result = app(QueryTimeout::class)->run(
            fn () => static::generateSleepQuery(3),
            2,
            null,
            function (QueryTimeoutException $e) use (&$called) {
                $called = true;
            },
            'fallback_value',
            false
        );

        $this->assertTrue($called, 'whenTimeout callback should have been called');
        $this->assertEquals('fallback_value', $result->getResult());
    }

    public function test_when_timeout_with_no_throw_returns_null_fallback()
    {
        $result = app(QueryTimeout::class)->run(
            fn () => static::generateSleepQuery(3),
            2,
            null,
            fn () => null,
            null,
            false
        );

        $this->assertNull($result->getResult());
    }

    public function test_builder_when_timeout_callback_is_called_on_timeout()
    {
        $called = false;

        $this->assertThrows(function () use (&$called) {
            app(QueryTimeout::class)
                ->build()
                ->timeout(2)
                ->on('default')
                ->whenTimeout(function (QueryTimeoutException $e) use (&$called) {
                    $called = true;
                })
                ->for(fn () => static::generateSleepQuery(3))
                ->run();
        }, QueryTimeoutException::class);

        $this->assertTrue($called, 'Builder whenTimeout callback should have been called');
    }

    public function test_builder_when_timeout_callback_is_not_called_without_timeout()
    {
        $called = false;

        app(QueryTimeout::class)
            ->build()
            ->timeout(3)
            ->on('default')
            ->whenTimeout(function () use (&$called) {
                $called = true;
            })
            ->for(fn () => \DB::select('SELECT TRUE'))
            ->run();

        $this->assertFalse($called, 'Builder whenTimeout callback should not have been called');
    }

    public function test_builder_when_timeout_with_default_calls_callback_and_returns_default()
    {
        $called = false;

        $result = app(QueryTimeout::class)
            ->build()
            ->timeout(2)
            ->on('default')
            ->whenTimeout(function (QueryTimeoutException $e) use (&$called) {
                $called = true;
            })
            ->default('default_value')
            ->for(fn () => static::generateSleepQuery(3))
            ->run();

        $this->assertTrue($called, 'Builder whenTimeout callback should have been called');
        $this->assertEquals('default_value', $result->getResult());
    }

    public function test_builder_when_timeout_with_default_null()
    {
        $result = app(QueryTimeout::class)
            ->build()
            ->timeout(2)
            ->on('default')
            ->whenTimeout(fn () => null)
            ->default(null)
            ->for(fn () => static::generateSleepQuery(3))
            ->run();

        $this->assertNull($result->getResult());
    }

    public function test_non_timeout_exception()
    {
        $this->expectException(QueryException::class);

        app(QueryTimeout::class)
            ->build()
            ->timeout(2)
            ->for(fn () => \DB::select('WRONG QUERY'))
            ->run();
    }

    public function test_non_timeout_exception_with_default()
    {
        $this->expectException(QueryException::class);

        app(QueryTimeout::class)
            ->build()
            ->timeout(2)
            ->default('default_value')
            ->for(fn () => \DB::select('WRONG QUERY'))
            ->run();
    }

    protected static function generateSleepQuery(int $seconds)
    {
        $driver = config('database.connections.default.driver');
        $sleepFnc = "SELECT SLEEP($seconds)";

        if ($driver === 'pgsql') {
            $sleepFnc = 'PG_SLEEP';
        }

        if ($driver === 'mysql') {
            $sleepFnc = <<<SQL
                SET SESSION cte_max_recursion_depth = 999999999;

                WITH RECURSIVE loop_cte AS (
                    SELECT 1 AS iteration, NOW(6) AS start_time

                    UNION ALL

                    SELECT iteration + 1, start_time
                    FROM loop_cte
                    WHERE TIMESTAMPDIFF(MICROSECOND, start_time, NOW(6)) < 3000000
                )
                SELECT
                    COUNT(*)
                FROM loop_cte;
            SQL;
        }

        return \DB::select(sprintf('SELECT %s(%d)', $sleepFnc, $seconds));
    }
}
