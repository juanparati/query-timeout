<?php

namespace Juanparati\QueryTimeout\Test\Unit;

use Juanparati\QueryTimeout\Facades\QueryTimeoutFacade as DbTimeoutFacade;
use Juanparati\QueryTimeout\QueryTimeout;
use Juanparati\QueryTimeout\Test\TimeoutTestBase;

class FacadeTest extends TimeoutTestBase
{
    public function test_facade_resolves_to_db_timeout_instance()
    {
        $this->assertInstanceOf(QueryTimeout::class, DbTimeoutFacade::getFacadeRoot());
    }

    public function test_facade_resolves_singleton()
    {
        $this->assertSame(
            DbTimeoutFacade::getFacadeRoot(),
            DbTimeoutFacade::getFacadeRoot()
        );
    }
}
