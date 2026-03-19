<?php

namespace Juanparati\QueryTimeout;

use Illuminate\Database\Connection;

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
     * @param int|float $seconds The timeout duration in seconds
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
     * @param string|Connection $connection The connection name or instance
     * @return $this
     */
    public function on(string|Connection $connection): static
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Execute the query with the configured timeout and connection.
     * Alias for query() when callback is already set.
     *
     * @return QueryTimeout
     */
    public function run(): QueryTimeout
    {
        return ($this->service)($this->callback, $this->seconds, $this->connection);
    }

    /**
     * Set the query callback. Allows setting the callback in a fluent way.
     *
     * @param callable $callback The query callback to execute
     * @return $this
     */
    public function for(callable $callback): static
    {
        $this->callback = $callback;
        return $this;
    }
}
