<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Database;

use Cycle\Database\Config;
use PDO;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\MySQLTagDriver;

class DatabaseConfig
{
    private const string HOST     = 'localhost';
    private const string USERNAME = 'root';
    private const string PASSWORD = 'toor';
    private const string DATABASE = 'cycle_sql_tagger';

    public static function get(): Config\DatabaseConfig
    {
        return new Config\DatabaseConfig([
            'default'   => 'mariaDb',
            'databases' => [
                'mariaDb' => [
                    'connection' => 'mariaDbDsn',
                ],
            ],
            'connections' => [
                'mariaDbDsn' => self::getDriverConfig(),
            ],
        ]);
    }

    public static function getDriverConfig(): Config\MySQLDriverConfig
    {
        return new Config\MySQLDriverConfig(
            connection: new Config\MySQL\DsnConnectionConfig(
                dsn: 'mysql:host=' . self::HOST . ';port=3306;dbname=' . self::DATABASE . ';charset=utf8mb4',
                user: self::USERNAME,
                password: self::PASSWORD,
                /* @phpstan-ignore-next-line */
                options: [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = 'TRADITIONAL', collation_connection = @@collation_database, wait_timeout = 10",
                ]
            ),
            driver: MySQLTagDriver::class,
            reconnect: false,
            queryCache: false,
            options: [
                'withDatetimeMicroseconds' => true,
                'logQueryParameters'       => true,
            ],
        );
    }
}
