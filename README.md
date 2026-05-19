# SquidIT - Cycle Database - SQL Tagger

Cycle Database decorator to add SQL comments to your queries.

## Features
* Tag Query & Execute database calls
* Tag All QueryBuilder queries
* Multi-Line comment support
* Optional database logger with read/write counts and captured SQL queries

## Use Case
When applications grow, it can be hard to keep track of the origins of an SQL Query.
This class will allow you to tag your query with an SQL comment.
Tagging your queries will help your DBAs in reporting back on slow performing SQL queries.

## Usage - example:

```php
<?php

declare(strict_types=1);

use Cycle\Database\Config;
use SquidIT\Cycle\Sql\Tagger\DatabaseManagerWithTagger;
use SquidIT\Cycle\Sql\Tagger\DatabaseWithTagger;

/**
 * Set up your cycle/database configuration
 * 
 * Make sure you select the following driver: 
 * SquidIT\Cycle\Sql\Tagger\Driver\MySQL\MySQLTagDriver::class
 */
/** @var DatabaseConfig $dbConfig */
$dbConfig = new Config\DatabaseConfig([
            ...
            'connections' => [
                'mariaDbDsn' => new Config\MySQLDriverConfig(
                    ...
                    driver: MySQLTagDriver::class,
                    ...
                ),
            ],
        ]);

$dbal = new DatabaseManagerWithTagger($dbConfig);

$database = $dbal->database();
$database->tagQueryWithComment('Filename: file.php, Method: MethodName, LineNr: 10');
$database->query('SELECT [QUERY]');

// After executing a SQL query the comment is removed
$database->tagQueryWithComment('Filename: file.php, Method: MethodName, LineNr: 10');
$database->execute('INSERT [QUERY]');

// the following will have the same result
$database->tagQueryWithComment('Filename: file.php, Method: MethodName, LineNr: 10');
$selectQuery = $database->select();
$selectQuery->fetchAll();

# or:

$selectQuery = $database->select();
$selectQuery->tagQueryWithComment('Filename: file.php, Method: MethodName, LineNr: 10');
$selectQuery->fetchAll();

// After selecting a table
$database = $database->database();
$database->table('tableName')
    ->tagQueryWithComment([
        'File: ' . __FILE__,
        'Line: ' . __LINE__,
        'Function: ' . __METHOD__
     ])
    ->update([
        'column1' => 'value1',
        'column2' => 'value2',
    ])
    ->where([
        'comment_id' => ['=' => 1],
    ])
    ->run();
```

## Optional database logger

`DbLogger` can be attached to the database manager when you want to inspect executed queries during tests, debugging, or local development.
It keeps separate read and write counters, stores the executed SQL, and can optionally print colorized query output to the terminal.

```php
<?php

declare(strict_types=1);

use SquidIT\Cycle\Sql\Tagger\DatabaseManagerWithTagger;
use SquidIT\Cycle\Sql\Tagger\Logger\DbLogger;

$dbal = new DatabaseManagerWithTagger($dbConfig);

$dbLogger = new DbLogger();
$dbal->setLogger($dbLogger);

// Optional: show colorized SQL output in the terminal.
$dbLogger->enableDisplay();

$database = $dbal->database();
$database->query('SELECT * FROM users');
$database->execute('UPDATE users SET active = 1 WHERE user_id = 1');

$readCount  = $dbLogger->getReadCount();
$writeCount = $dbLogger->getWriteCount();
$queries    = $dbLogger->getQueries();

// Clear captured queries and counters when needed.
$dbLogger->reset();
```

`DbLoggerFactory` is also available when you want to use Cycle Database's logger-factory hook and create one logger per driver.

### Database log output (first example only)
```
241128 17:21:54     29 Connect test@localhost on cycle_sql_tagger using TCP/IP
                    29 Query    /* 2024-11-28T16:21:54.401123: Filename: file.php, Method: MethodName, LineNr: 10 */
                                 SELECT [QUERY]
                    29 Quit	
```
