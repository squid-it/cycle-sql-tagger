<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger;

use SquidIT\Cycle\Sql\Tagger\Logger\Abstract\AbstractDbLogger;

class DbLogger extends AbstractDbLogger
{
    /** @var array<int, string> */
    private array $readQueries = [];

    /** @var array<int, string> */
    private array $writeQueries = [];

    /**
     * @return array{read: array<int, string>, write: array<int, string>}
     */
    public function getQueries(): array
    {
        return [
            'read'  => $this->readQueries,
            'write' => $this->writeQueries,
        ];
    }

    protected function storeReadQuery(string $query): void
    {
        $this->readQueries[] = $query;
    }

    protected function storeWriteQuery(string $query): void
    {
        $this->writeQueries[] = $query;
    }

    protected function resetStoredQueries(): void
    {
        $this->readQueries  = [];
        $this->writeQueries = [];
    }
}
