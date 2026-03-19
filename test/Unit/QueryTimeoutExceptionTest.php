<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;
use PHPUnit\Framework\TestCase;

class QueryTimeoutExceptionTest extends TestCase
{
    public function test_exception_has_correct_code(): void
    {
        $exception = new QueryTimeoutException('default');

        $this->assertEquals(70100, $exception->getCode());
    }

    public function test_exception_has_correct_message(): void
    {
        $exception = new QueryTimeoutException('default');

        $this->assertEquals('Query execution was interrupted', $exception->getMessage());
    }

    public function test_exception_stores_connection_name(): void
    {
        $exception = new QueryTimeoutException('mysql_secondary');

        $this->assertEquals('mysql_secondary', $exception->getConnectionName());
    }

    public function test_connection_name_is_publicly_accessible(): void
    {
        $exception = new QueryTimeoutException('my_connection');

        $this->assertEquals('my_connection', $exception->connectionName);
    }

    public function test_exception_extends_pdo_exception(): void
    {
        $exception = new QueryTimeoutException('default');

        $this->assertInstanceOf(\PDOException::class, $exception);
    }

    public function test_exception_preserves_previous_exception(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new QueryTimeoutException('default', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_exception_copies_error_info_from_pdo_previous(): void
    {
        $previous = new \PDOException('PDO error');
        $previous->errorInfo = ['70100', 70100, 'max_statement_time exceeded'];

        $exception = new QueryTimeoutException('default', $previous);

        $this->assertEquals(['70100', 70100, 'max_statement_time exceeded'], $exception->errorInfo);
    }

    public function test_exception_does_not_copy_error_info_from_non_pdo_previous(): void
    {
        $previous = new \RuntimeException('Some error');
        $exception = new QueryTimeoutException('default', $previous);

        $this->assertNull($exception->errorInfo);
    }

    public function test_exception_without_previous(): void
    {
        $exception = new QueryTimeoutException('default');

        $this->assertNull($exception->getPrevious());
    }
}
