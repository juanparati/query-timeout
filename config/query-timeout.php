<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default timeout
    |--------------------------------------------------------------------------
    |
    | Default query timeout in seconds.
    |
    | By default, this is set to the maximum execution time minus 5 seconds.
    | If you use max_execution_time as your baseline, always maintain at least
    | a 5-second buffer; database servers may not terminate queries at the
    | exact moment you specify.
    |
    */
    'default_timeout' => (ini_get('max_execution_time') ?: 100) - 10,

    /*
    |--------------------------------------------------------------------------
    | Runtime resolution
    |--------------------------------------------------------------------------
    |
    | The 'getLastQueryTime' method will return the time in this specified unit.
    |
    | Runtime resolution. Available values:
    | - microsecond
    | - millisecond (Default)
    | - second
    |
    */
    'resolution' => 'millisecond',

    /*
    |--------------------------------------------------------------------------
    | MariaDB specific configuration
    |--------------------------------------------------------------------------
    |
    */
    'mariadb' => [],

    /*
    |--------------------------------------------------------------------------
    | PostgreSQL specific configuration
    |--------------------------------------------------------------------------
    |
    */
    'pgsql' => [],

    /*
    |--------------------------------------------------------------------------
    | MySQL specific configuration
    |--------------------------------------------------------------------------
    |
    | Specific configuration for MySQL.
    | Note: Do not use this configuration with MariaDB.
    |
    */
    'mysql' => [

        /*
        | MySQL has a bug that prevents detecting query expiration for pure computational queries,
        | so it requires to re-check manually when a query is expired.
        |
        | If 'recheck_timeout' is set to false, you may face the risk of receiving incomplete results
        | when a pure computational query is executed.
        |
        | See: https://bugs.mysql.com/bug.php?id=120108
        |
        */
        'recheck_timeout' => true,
    ],
];
