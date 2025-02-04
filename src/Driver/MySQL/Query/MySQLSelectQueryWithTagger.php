<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query;

use Cycle\Database\Driver\MySQL\Query\MySQLSelectQuery;
use Cycle\Database\StatementInterface;
use SquidIT\Cycle\Sql\Tagger\Trait\SQLStatementWithTagTrait;
use SquidIT\Cycle\Sql\Tagger\Trait\WithTaggerTrait;

class MySQLSelectQueryWithTagger extends MySQLSelectQuery
{
    use WithTaggerTrait;
    use SQLStatementWithTagTrait;

    public function run(): StatementInterface
    {
        $statement     = parent::run();
        $this->comment = null;

        return $statement;
    }
}
