<?php

namespace Juanparati\QueryTimeout\Drivers;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;

class MysqlQueryTimeoutDriver extends MariadbQueryTimeoutDriver
{
    protected const VARIABLE_NAME = 'max_execution_time';

    public function __construct(protected Connection $connection, protected array $config = [])
    {
        if (! $this->isCompatible()) {
            throw new \RuntimeException('This driver is only compatible with MySQL');
        }
    }

    public function setTimeout(float|int $seconds): void
    {
        // MySQL requires milliseconds
        parent::setTimeout($seconds * 1000);
    }

    public function captureTimeoutException(\Throwable $error): \Throwable
    {
        // It will detect timeout for MySQL
        if ($error instanceof QueryException
            && $error->getCode() === 'HY000'
            && str($error->getMessage())->contains('time exceeded', true)
        ) {
            return new QueryTimeoutException($this->connection->getName(), $error);
        }

        return $error;
    }

    public function canRaiseTimeoutException(): bool
    {
        // Unfortunately, MySQL doesn't raise exceptions for pure computational queries,
        // so we have to re-check manually that query was expired
        // @see https://bugs.mysql.com/bug.php?id=120108
        return !($this->config['recheck_timeout'] ?? true);
    }
}
