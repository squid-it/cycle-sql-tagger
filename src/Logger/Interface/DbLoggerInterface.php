<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger\Interface;

use Psr\Log\LoggerInterface;

interface DbLoggerInterface extends LoggerInterface
{
    public function getWriteCount(): int;

    public function getReadCount(): int;

    /**
     * @return array{read: array<int, string>, write: array<int, string>}
     */
    public function getQueries(): array;

    public function reset(): void;

    public function enableDisplay(): void;

    public function disableDisplay(): void;
}
