<?php

namespace Juanparati\QueryTimeout;

use Illuminate\Database\Connection;
use Juanparati\QueryTimeout\Exceptions\QueryTimeoutException;

/**
 * Fluent interface builder for QueryTimeout.
 *
 * Provides a chainable API for configuring and executing query timeouts.
 *
 * @example
 * QueryTimeout()
 *     ->build()
 *     ->timeout(5)
 *     ->on('mysql')
 *     ->whenTimeout(fn($e) => logs()->error($e->getMessage()))
 *     ->for(fn() => User::all())
 *     ->run();
 */
class QueryTimeoutBuilder
{
    /**
     * The timeout in seconds.
     */
    protected int|float|null $seconds = null;

    /**
     * The database connection name or instance.
     */
    protected string|Connection|null $connection = null;

    /**
     * The query callback to execute.
     */
    protected mixed $callback = null;

    /**
     * Callback to run when the query times out.
     *
     * @var mixed|null
     */
    protected mixed $whenTimeoutCallback = null;

    /**
     * Default value to return if the query times out instead of throwing an exception.
     */
    protected mixed $defaultValue = null;

    /**
     * The QueryTimeout service instance.
     */
    protected QueryTimeout $service;

    /**
     * Create a new QueryTimeoutBuilder instance.
     */
    public function __construct(?QueryTimeout $service = null)
    {
        $this->service = $service ?? app(QueryTimeout::class);
    }

    /**
     * Set the timeout in seconds.
     *
     * @param  int|float  $seconds  The timeout duration in seconds
     * @return $this
     */
    public function timeout(int|float $seconds): static
    {
        $this->seconds = $seconds;

        return $this;
    }

    /**
     * Set the database connection.
     *
     * @param  string|Connection  $connection  The connection name or instance
     * @return $this
     */
    public function on(string|Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Execute a callback when the query times out.
     *
     * @return $this
     */
    public function whenTimeout(callable $callback): static
    {
        $this->whenTimeoutCallback = $callback;

        return $this;
    }

    /**
     * Set the default value to return if the query times out.
     * When a default value is set, the QueryTimeoutException is never thrown.
     *
     * @return $this
     */
    public function default(mixed $value): static
    {
        $this->defaultValue = new QueryTimeoutDefaultResult($value);

        return $this;
    }

    /**
     * Execute the query with the configured timeout and connection.
     * Alias for query() when callback is already set.
     *
     * @throws \Throwable
     */
    public function run(): QueryTimeout
    {
        if (is_null($this->callback)) {
            throw new \RuntimeException('Query callback is not set.');
        }

        return ($this->service)(
            $this->callback,
            $this->seconds,
            $this->connection,
            $this->whenTimeoutCallback,
            $this->defaultValue instanceof QueryTimeoutDefaultResult ? $this->defaultValue->value() : null,
            ! $this->defaultValue instanceof QueryTimeoutDefaultResult
        );
    }

    /**
     * Set the query callback. Allows setting the callback in a fluent way.
     *
     * @param  callable  $callback  The query callback to execute
     * @return $this
     */
    public function for(callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }
}
