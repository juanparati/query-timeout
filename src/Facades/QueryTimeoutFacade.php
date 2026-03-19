<?php

namespace Juanparati\QueryTimeout\Facades;

use Illuminate\Support\Facades\Facade;
use Juanparati\QueryTimeout\QueryTimeout as QueryTimeoutService;

class QueryTimeoutFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryTimeoutService::class;
    }
}
