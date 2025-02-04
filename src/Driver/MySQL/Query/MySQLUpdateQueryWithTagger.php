<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query;

use Cycle\Database\Driver\MySQL\Query\MySQLUpdateQuery;
use SquidIT\Cycle\Sql\Tagger\Trait\SQLStatementWithTagTrait;
use SquidIT\Cycle\Sql\Tagger\Trait\WithTaggerTrait;

class MySQLUpdateQueryWithTagger extends MySQLUpdateQuery
{
    use WithTaggerTrait;
    use SQLStatementWithTagTrait;

    /**
     * Affect queries will return count of affected rows.
     */
    public function run(): int
    {
        $int           = parent::run();
        $this->comment = null;

        return $int;
    }
}
