<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Driver\MySQL;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\MySQL\MySQLCompiler;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\MySQL\MySQLHandler;
use Cycle\Database\Query\QueryBuilder;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\InsertQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLDeleteQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLSelectQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLUpdateQueryWithTagger;

final class MySQLTagDriver extends MySQLDriver
{
    public static function create(DriverConfig $config): static
    {
        return new static(
            $config,
            new MySQLHandler(),
            new MySQLCompiler('``'),
            new QueryBuilder(
                new MySQLSelectQueryWithTagger(),
                new InsertQueryWithTagger(),
                new MySQLUpdateQueryWithTagger(),
                new MySQLDeleteQueryWithTagger(),
            ),
        );
    }
}
