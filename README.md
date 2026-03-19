![test](https://github.com/juanparati/laravel-timeout/actions/workflows/on_push.yml/badge.svg)

# Query Timeout ⏰

A Laravel database library that implements query timeouts at the database level, helping you implement the [circuit breaker pattern](https://en.wikipedia.org/wiki/Circuit_breaker_design_pattern).

Compatible with the following RDBMS:
- MariaDB
- MySQL
- PostgreSQL

## Installation

```sh
composer require juanparati/query-timeout
```

## How it works.

### Running queries

Use the `QueryTimeout` facade to set a maximum execution time for your queries:

```PHP
\QueryTimeout::run(
    fn() => \DB::select('SELECT SLEEP(4)'),     // Your query comes here (Use pg_sleep for testing with PostgreSQL)
    3                                     ,     // Interrupt if a query takes more than 3 seconds (Keep null for default timeout)
    'myconnection'                              // Database connection (Keep null for the default connection)
    fn() => logs()->error('Timeout here')       // Run this callback before throwing QueryTimeoutException
);
```

or using the fluent builder:

```PHP
\QueryTimeout::build()
    ->for(fn() => \DB::select('SELECT SLEEP(4)'))
    ->timeout(3)
    ->on('myconnection')
    ->whenTimeout(fn() => logs()->error('Timeout here'))
    ->run();
```

In the previous example if the query exceeds the specified timeout (3 seconds), it will send an error to the log and throw a `\Juanparati\QueryTimeout\QueryTimeoutException`.


### Returning direct results

Instead of passing the results as reference like in the following example:

```PHP
$users = null;

\QueryTimeout::run(
    function() use (&$users) => $users = User::where('name', 'like', 'john%')->get()
);
```

you can also get the results directly:

```PHP
$users = \QueryTimeout::run(
    fn() => User::where('name', 'like', 'john%')->get()
)->getResult();
```

### Obtain execution time

RDBMS are not very accurate stopping queries, but you can get the real execution time of the query using the `getQueryTime` method:

```PHP
$queryTime = \QueryTimeout::run(
    fn() => \DB::select('SELECT SLEEP(4)')
)->getQueryTime();
```

The configuration key ´resolution´ defines the precision of the time measurement output.

## How it works under the hood

Instead of using co-routines or parallel execution monitoring, this library leverages native database features:
- MariaDB: [max_statement_time](https://mariadb.com/docs/server/ha-and-performance/optimization-and-tuning/system-variables/server-system-variables#max_statement_time)
- MySQL: [max_execution_time](https://dev.mysql.com/doc/refman/8.4/en/optimizer-hints.html#optimizer-hints-execution-time)
- PostgreSQL: [statement_timeout](https://www.postgresql.org/docs/current/runtime-config-client.html#GUC-STATEMENT-TIMEOUT)

The timeout mechanism works by:
1. Setting the timeout value for the database session.
2. Executing the query.
3. Restoring the original timeout value even if the main query fails.

```
╔════════════════════════════════════════════════════════════════════════════════════════════════════╗
║ Example flow for MariaDB                                                                           ║
║                                                                                                    ║
║     ┌─────────────────────────────────────────┐                                                    ║
║   1.│SET @@SESSION.max_statement_time=3;      │ ◀─── Set the desired maximum time for the session  ║
║     └─────────────────────────────────────────┘                                                    ║
║     ┌─────────────────────────────────────────┐                                                    ║
║   2.│SELECT * FROM USERS;                     │ ◀─── Execute the query                             ║
║     └─────────────────────────────────────────┘                                                    ║
║     ┌─────────────────────────────────────────┐                                                    ║
║   3.│SET @@SESSION.max_statement_time=0;      │ ◀─── Restore the original session maximum time     ║
║     └─────────────────────────────────────────┘                                                    ║
╚════════════════════════════════════════════════════════════════════════════════════════════════════╝
```

### Limitations

### MySQL-specific

- Only "select" queries are timed out in MySQL.
- Unfortunately, for pure computational queries MySQL kills the query silently without raising any error, so in this case this library determines when a query is timed out measuring the execution time and creating artificially an exception. This method may not be very accurate (See the configuration `mysql.recheck_timeout` for changing this behavior).

### MariaDB-specific

- Old MariaDB embedded servers may not work properly.
- COMMIT statements don't timeout in Galera clusters.


### General Limitations:

- May be unreliable with persistent connections or connection pools in distributed environments


## Best Practices

1. Keep application logic outside the closure.

✅ Recommended:

```PHP
$users = null;

\QueryTimeout::run(
    function() use (&$users) => $users = User::where('name', 'like', 'john%')->get(),
);

foreach ($users as $user) {
    if ($user->password_expiration > now()) {
        ... // Your application logic
    }
}
```

❌ Not recommended:

```PHP
\QueryTimeout::run(
    function() {
        $users = User::where('name', 'like', 'john%')->get()

        foreach ($users as $user) {
            if ($user->password_expiration > now()) {
                ... // Your application logic
            }
        }
    }
);
```

2. In MySQL, try to use only one query per closure (Be cautious with ORM operations that might trigger multiple queries).


## Configuration

### Publish configuration (Optional)

```BASH
artisan vendor:publish --tag="query-timeout"
```
