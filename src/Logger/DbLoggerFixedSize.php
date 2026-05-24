<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger;

use InvalidArgumentException;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use SplDoublyLinkedList;
use SplQueue;
use SquidIT\Cycle\Sql\Tagger\Logger\Interface\DbLoggerInterface;
use Stringable;

use function iterator_to_array;
use function str_starts_with;
use function strtolower;

class DbLoggerFixedSize implements DbLoggerInterface
{
    use LoggerTrait;

    private readonly int $maxSize;

    private bool $display = false;

    private int $countWrites = 0;

    private int $countReads = 0;

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

    public function getWriteCount(): int
    {
        return $this->countWrites;
    }

    public function getReadCount(): int
    {
        return $this->countReads;
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

    public function reset(): void
    {
        $this->readQueries  = self::createQueryQueue();
        $this->writeQueries = self::createQueryQueue();
        $this->countWrites  = 0;
        $this->countReads   = 0;
    }

    /**
     * @param array{elapsed?: bool|float|int|string|null, ...} $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $messageAsString = (string) $message;

        if (empty($context['elapsed']) === false) {
            $sql = strtolower($messageAsString);

            if (
                str_starts_with($sql, 'insert')
                || str_starts_with($sql, 'update')
                || str_starts_with($sql, 'delete')
            ) {
                $this->countWrites++;
                $this->addQuery($this->writeQueries, $messageAsString);
            } else {
                $this->countReads++;
                $this->addQuery($this->readQueries, $messageAsString);
            }
        }

        if ($this->display === false) {
            return;
        }

        if ($level === LogLevel::ERROR) {
            echo " \n! \033[31m" . $messageAsString . "\033[0m";
        } elseif ($level === LogLevel::ALERT) {
            echo " \n! \033[35m" . $messageAsString . "\033[0m";
        } elseif (str_starts_with($messageAsString, 'SHOW')) {
            echo " \n> \033[34m" . $messageAsString . "\033[0m";
        } elseif (str_starts_with($messageAsString, 'SELECT')) {
            echo " \n> \033[32m" . $messageAsString . "\033[0m";
        } elseif (str_starts_with($messageAsString, 'INSERT')) {
            echo " \n> \033[36m" . $messageAsString . "\033[0m";
        } else {
            echo " \n> \033[33m" . $messageAsString . "\033[0m";
        }
    }

    public function enableDisplay(): void
    {
        $this->display = true;
    }

    public function disableDisplay(): void
    {
        $this->display = false;
    }

    private function addQuery(SplQueue $queryQueue, string $query): int
    {
        $queryQueue->enqueue($query);

        if ($queryQueue->count() > $this->maxSize) {
            $queryQueue->dequeue();
        }

        return $queryQueue->count();
    }

    /**
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
        $queryQueue = new SplQueue();
        $queryQueue->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);

        return $queryQueue;
    }
}
