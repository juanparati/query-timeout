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
     * @param callable|null $callback Database query
     * @param int|float|null $seconds Timeout in seconds (Default: See timeout config)
     * @param string|Connection|null $con Connection (Default: default connection)
     * @param callable|null $whenTimeout Callback executed when callback execution expires
     * @param mixed $fallbackValue The default result value used when throwTimeoutException is false
     * @param bool $throwTimeoutException Indicates whether to throw a QueryTimeoutException or return the default result value
     * @return QueryTimeout|QueryTimeoutBuilder
     */
    public function __invoke(
        callable|null          $callback = null,
        int|float|null         $seconds = null,
        string|Connection|null $con = null,
        callable|null          $whenTimeout = null,
        mixed                  $fallbackValue = null,
        bool                   $throwTimeoutException = true
    ): static|QueryTimeoutBuilder
    {
        if (null === $callback) {
            return $this->build();
        }

        $this->lastResult    = new QueryTimeoutDefaultResult($fallbackValue);
        $this->lastQueryTime = null;

        $seconds = $seconds ?? $this->config['default_timeout'];

        $con = $con instanceof Connection
            ? $con : ($con ? \DB::connection($con) : \DB::connection());

        $connectionName = $con->getName();

        if (!isset($this->drivers[$connectionName])) {
            $driverName = str($con->getDriverName())
                ->lower();

            $driverClass = $driverName
                ->ucfirst()
                ->prepend('\\Juanparati\\QueryTimeout\\Drivers\\')
                ->append('QueryTimeoutDriver')
                ->toString();

            $this->drivers[$connectionName] = new ($driverClass)(
                $con,
                $this->config[$driverName->toString()] ?? []
            );

            $this->drivers[$connectionName]->saveDefaultTimeout();
        }

        $con = $this->drivers[$connectionName];
        $con->setTimeout($seconds);

        $error      = null;
        $startTimer = now();

        try {
            $this->lastResult = $callback();
        } catch (\Throwable $e) {
            $error = $e;
        } finally {
            // It's important to reset the default timeout after the callback execution.
            $con->resetTimeout();
        }

        $error = $error ? $con->captureTimeoutException($error) : null;

        $runtime = $startTimer->diffInMicroseconds(now());

        // Unfortunately, some RDBMS like MySQL doesn't raise any error or warning when calculated queries are used.
        // so we have to calculate the runtime and artificially to create the exception.
        // This method is non-deterministic because the PHP code will consume runtime.
        if (!$error && !$con->canRaiseTimeoutException()) {
            if ($seconds < ($runtime / 1e6)) {
                $error = new QueryTimeoutException($connectionName);
            }
        }

        $this->lastQueryTime = $runtime / $this->getTimeResolutionScale();

        if ($error) {
            if ($error instanceof QueryTimeoutException) {

                if ($whenTimeout) {
                    $whenTimeout($error);
                }

                if ($throwTimeoutException === true) {
                    throw $error;
                }
            } else {
                // All other errors are always thrown.
                throw $error;
            }
        }

        return $this;
    }

    /**
     * Builder instance.
     *
     * @return QueryTimeoutBuilder
     */
    public function build(): QueryTimeoutBuilder
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
        return $this->lastResult instanceof QueryTimeoutDefaultResult ? $this->lastResult->value() : $this->lastResult;
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
