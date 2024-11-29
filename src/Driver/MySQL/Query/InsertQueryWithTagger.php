<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query;

use Cycle\Database\Query\InsertQuery;
use SquidIT\Cycle\Sql\Tagger\Trait\SQLStatementWithTagTrait;
use SquidIT\Cycle\Sql\Tagger\Trait\WithTaggerTrait;

class InsertQueryWithTagger extends InsertQuery
{
    use WithTaggerTrait;
    use SQLStatementWithTagTrait;
}
