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

    /**
     * Run the query and return last insert id.
     * Returns an assoc array of values if multiple columns were specified as returning columns.
     *
     * @return array<non-empty-string, mixed>|int|non-empty-string|null
     */
    public function run(): mixed
    {
        $insertResult  = parent::run();
        $this->comment = null;

        return $insertResult;
    }
}
