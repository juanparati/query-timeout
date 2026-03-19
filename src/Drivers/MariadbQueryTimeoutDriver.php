<?php

namespace Juanparati\QueryTimeout\Drivers;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Juanparati\QueryTimeout\Contracts\QueryTimeoutDriver;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;

class MariadbQueryTimeoutDriver implements QueryTimeoutDriver
{
    protected const VARIABLE_NAME = 'max_statement_time';

    protected int|float $defaultTimeout;

    public function __construct(protected Connection $connection, protected array $config = [])
    {
        if (! $this->isCompatible()) {
            throw new \RuntimeException('This driver is only compatible with MariaDB');
        }
    }

    public function setTimeout(float|int $seconds): void
    {
        $this->connection
            ->statement(sprintf('SET @@SESSION.%s=%d', static::VARIABLE_NAME, $seconds));
    }

    public function saveDefaultTimeout(): int|float
    {
        $default = $this->connection
            ->select(sprintf('SELECT @@SESSION.%s AS value', static::VARIABLE_NAME))[0]->value;

        return $this->defaultTimeout = $default;
    }

    public function isCompatible(): bool
    {
        return ! empty(
            $this->connection
                ->select(sprintf("SHOW VARIABLES LIKE '%s'", static::VARIABLE_NAME))
        );
    }

    public function resetTimeout(): void
    {
        $this->setTimeout($this->defaultTimeout);
    }

    public function throwTimeoutException(\Throwable $error): never
    {
        // It will detect timeout for MariaDB
        if ($error instanceof QueryException
            && $error->getCode() == 70100
            && str($error->getMessage())->contains('max_statement_time', true)
        ) {
            throw new QueryTimeoutException($this->connection->getName(), $error);
        }

        throw $error;
    }

    public function canRaiseTimeoutException(): bool
    {
        return true;
    }
}
