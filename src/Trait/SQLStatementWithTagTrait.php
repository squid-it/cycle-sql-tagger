<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Trait;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\QueryParameters;

trait SQLStatementWithTagTrait
{
    /**
     * Generate SQL query.
     * Must have associated driver instance.
     */
    public function sqlStatement(?QueryParameters $parameters = null): string
    {
        $this->driver === null and throw new BuilderException('Unable to build query without associated driver');

        /** @phpstan-ignore-next-line */
        return $this->createSqlComment() . $this->driver->getQueryCompiler()->compile(
            $parameters ?? new QueryParameters(),
            $this->prefix ?? '',
            $this,
        );
    }
}
