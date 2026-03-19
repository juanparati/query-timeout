<?php

namespace Juanparati\QueryTimeout;

class QueryTimeoutDefaultResult
{
    public function __construct(protected mixed $defaultValue) {}

    public function value(): mixed
    {
        return is_callable($this->defaultValue) ? ($this->defaultValue)() : $this->defaultValue;
    }
}
