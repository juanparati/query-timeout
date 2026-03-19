<?php

namespace Juanparati\QueryTimeout\Exceptions;

use PDOException;

/**
 * Custom exception for query timeout.
 */
class QueryTimeoutException extends PDOException
{
    /**
     * The database connection name.
     */
    public string $connectionName;

    /**
     * Create a new query exception instance.
     */
    public function __construct(string $connectionName, ?\Throwable $previous = null)
    {
        parent::__construct('', 70100, $previous);

        $this->connectionName = $connectionName;
        $this->code = 70100;
        $this->message = 'Query execution was interrupted';

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Get the connection name for the query.
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}
