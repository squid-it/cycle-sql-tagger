<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger;

use InvalidArgumentException;
use SplQueue;
use SquidIT\Cycle\Sql\Tagger\Logger\Abstract\AbstractDbLogger;

use function iterator_to_array;

class DbLoggerFixedSize extends AbstractDbLogger
{
    private readonly int $maxSize;

    /** @var SplQueue<string> */
    private SplQueue $readQueries;

    /** @var SplQueue<string> */
    private SplQueue $writeQueries;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(int $maxSize = 1000)
    {
        if ($maxSize <= 0) {
            throw new InvalidArgumentException(static::class . ': maxSize must be greater than zero.');
        }

        $this->maxSize      = $maxSize;
        $this->readQueries  = self::createQueryQueue();
        $this->writeQueries = self::createQueryQueue();
    }

    /**
     * @return array{read: array<int, string>, write: array<int, string>}
     */
    public function getQueries(): array
    {
        return [
            'read'  => $this->queueToArray($this->readQueries),
            'write' => $this->queueToArray($this->writeQueries),
        ];
    }

    protected function storeReadQuery(string $query): void
    {
        $this->addQuery($this->readQueries, $query);
    }

    protected function storeWriteQuery(string $query): void
    {
        $this->addQuery($this->writeQueries, $query);
    }

    protected function resetStoredQueries(): void
    {
        $this->readQueries  = self::createQueryQueue();
        $this->writeQueries = self::createQueryQueue();
    }

    /**
     * @param SplQueue<string> $queryQueue
     */
    private function addQuery(SplQueue $queryQueue, string $query): void
    {
        $queryQueue->enqueue($query);

        if ($queryQueue->count() > $this->maxSize) {
            $queryQueue->dequeue();
        }
    }

    /**
     * @param SplQueue<string> $queryQueue
     *
     * @return array<int, string>
     */
    private function queueToArray(SplQueue $queryQueue): array
    {
        /** @var array<int, string> */
        return iterator_to_array($queryQueue, false);
    }

    /**
     * @return SplQueue<string>
     */
    private static function createQueryQueue(): SplQueue
    {
        return new SplQueue();
    }
}
