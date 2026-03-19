<?php

namespace Juanparati\QueryTimeout;

use Illuminate\Database\Connection;
use Juanparati\QueryTimeout\Contracts\QueryTimeoutDriver;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;

/**
 * Provides a control method through a closure for controlling query timeouts.
 *
 * It facilitates the implementation of a circuit-break pattern.
 */
class QueryTimeout
{
    /**
     * Timeout drivers cache
     *
     * @var array<string, QueryTimeoutDriver>
     */
    protected array $drivers = [];


    /**
     * Query result
     *
     * @var mixed
     */
    protected mixed $lastResult = null;


    /**
     * Query execution time
     *
     * @var int|null
     */
    protected int|null $lastQueryTime = null;


    public function __construct(protected array $config = [])
    {
    }


    /**
     * Run query with timeout.
     *
     * @param callable $callback
     * @param int|float|null $seconds
     * @param string|Connection|null $connection
     * @return $this
     */
    public function __invoke(
        callable|null          $callback = null,
        int|float|null         $seconds = null,
        string|Connection|null $connection = null
    ): static|QueryTimeoutBuilder
    {
        if (null === $callback) {
            return $this->build();
        }

        $this->lastResult    = null;
        $this->lastQueryTime = null;

        $seconds = $seconds ?? $this->config['default_timeout'];

        $connection = $connection instanceof Connection
            ? $connection : ($connection ? \DB::connection($connection) : \DB::connection());

        $connectionName = $connection->getName();

        if (!isset($this->drivers[$connectionName])) {
            $driverName = str($connection->getDriverName())
                ->lower();

            $driverClass = $driverName
                ->ucfirst()
                ->prepend('\\Juanparati\\QueryTimeout\\Drivers\\')
                ->append('QueryTimeoutDriver')
                ->toString();

            $this->drivers[$connectionName] = new ($driverClass)(
                $connection,
                $this->config[$driverName->toString()] ?? []
            );

            $this->drivers[$connectionName]->saveDefaultTimeout();
        }

        $connection = $this->drivers[$connectionName];
        $connection->setTimeout($seconds);

        $error      = null;
        $startTimer = now();

        try {
            $this->lastResult = $callback();
        } catch (\Throwable $e) {
            $error = $e;
        }

        // It's important to reset the default timeout after the callback execution.
        $connection->resetTimeout();

        if ($error) {
            $connection->throwTimeoutException($error);
        }

        $runtime = $startTimer->diffInMicroseconds(now());

        // Unfortunately, some RDBMS like MySQL doesn't raise any error or warning when calculated queries are used.
        // so we have to calculate the runtime and artificially to create the exception.
        // This method is non-deterministic because the PHP code will consume runtime.
        if (!$connection->canRaiseTimeoutException()) {
            if ($seconds < ($runtime / 1e6)) {
                throw new QueryTimeoutException($connectionName);
            }
        }

        $this->lastQueryTime = $runtime / $this->getTimeResolutionScale();

        return $this;
    }

    /**
     * Builder instance.
     *
     * @return QueryTimeoutBuilder
     */
    public function build() : QueryTimeoutBuilder
    {
        return new QueryTimeoutBuilder($this);
    }


    /**
     * Get query result.
     *
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->lastResult;
    }

    /**
     * Get query execution time.
     *
     * @return int|null
     */
    public function getQueryTime(): int|null
    {
        return $this->lastQueryTime;
    }

    /**
     * Get the scale for the time resolution.
     */
    protected function getTimeResolutionScale(): int|float
    {
        return match ($this->config['resolution']) {
            'microsecond', 'microseconds' => 1,
            'millisecond', 'milliseconds' => 1e3,
            default                       => 1e6,
        };
    }
}
