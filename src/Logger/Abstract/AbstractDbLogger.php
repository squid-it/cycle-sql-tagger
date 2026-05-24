<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger\Abstract;

use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use SquidIT\Cycle\Sql\Tagger\Logger\Interface\DbLoggerInterface;
use Stringable;

abstract class AbstractDbLogger implements DbLoggerInterface
{
    use LoggerTrait;

    private const array WRITE_SQL_VERB_LIST = [
        'insert',
        'update',
        'delete',
        'replace',
        'create',
        'alter',
        'drop',
        'truncate',
        'rename',
        'merge',
    ];

    private const array WRITE_SQL_PREFIX_LIST = [
        'load data',
    ];

    private bool $display = false;

    private int $countWrites = 0;

    private int $countReads = 0;

    public function getWriteCount(): int
    {
        return $this->countWrites;
    }

    public function getReadCount(): int
    {
        return $this->countReads;
    }

    public function reset(): void
    {
        $this->resetStoredQueries();
        $this->countWrites = 0;
        $this->countReads  = 0;
    }

    /**
     * @param array{elapsed?: bool|float|int|string|null, ...} $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $messageAsString = (string) $message;

        if (isset($context['elapsed']) === true) {
            $this->trackQuery($messageAsString);
        }

        if ($this->display === false) {
            return;
        }

        $this->displayMessage((string) $level, $messageAsString);
    }

    public function enableDisplay(): void
    {
        $this->display = true;
    }

    public function disableDisplay(): void
    {
        $this->display = false;
    }

    abstract protected function storeReadQuery(string $query): void;

    abstract protected function storeWriteQuery(string $query): void;

    abstract protected function resetStoredQueries(): void;

    private function trackQuery(string $query): void
    {
        if ($this->isWriteQuery($query) === true) {
            $this->storeWriteQuery($query);
            $this->countWrites++;

            return;
        }

        $this->storeReadQuery($query);
        $this->countReads++;
    }

    private function isWriteQuery(string $query): bool
    {
        $sql = $this->normalizeSqlForClassification($query);

        return $this->isWriteSql($sql);
    }

    private function isWriteSql(string $sql): bool
    {
        foreach (self::WRITE_SQL_VERB_LIST as $writeSqlVerb) {
            if (str_starts_with($sql, $writeSqlVerb) === true) {
                return true;
            }
        }

        foreach (self::WRITE_SQL_PREFIX_LIST as $writeSqlPrefix) {
            if (str_starts_with($sql, $writeSqlPrefix) === true) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSqlForClassification(string $query): string
    {
        return strtolower($this->stripLeadingSqlComments($query));
    }

    private function stripLeadingSqlComments(string $query): string
    {
        $sql = ltrim($query);

        while (
            str_starts_with($sql, '/*') === true
            || str_starts_with($sql, '--') === true
            || str_starts_with($sql, '#') === true
        ) {
            if (str_starts_with($sql, '/*') === true) {
                $commentEndPosition = strpos($sql, '*/');

                if ($commentEndPosition === false) {
                    return $sql;
                }

                $sql = ltrim(substr($sql, $commentEndPosition + 2));

                continue;
            }

            $sql = $this->stripLeadingSqlLineComment($sql);
        }

        return $sql;
    }

    private function stripLeadingSqlLineComment(string $sql): string
    {
        $lineEndPosition = strpos($sql, "\n");

        if ($lineEndPosition === false) {
            return '';
        }

        return ltrim(substr($sql, $lineEndPosition + 1));
    }

    private function displayMessage(string $level, string $message): void
    {
        $sqlForDisplayColor = $this->normalizeSqlForClassification($message);

        if ($level === LogLevel::ERROR) {
            echo " \n! \033[31m" . $message . "\033[0m";
        } elseif ($level === LogLevel::ALERT) {
            echo " \n! \033[35m" . $message . "\033[0m";
        } elseif (str_starts_with($sqlForDisplayColor, 'show')) {
            echo " \n> \033[34m" . $message . "\033[0m";
        } elseif (str_starts_with($sqlForDisplayColor, 'select')) {
            echo " \n> \033[32m" . $message . "\033[0m";
        } elseif ($this->isWriteSql($sqlForDisplayColor) === true) {
            echo " \n> \033[36m" . $message . "\033[0m";
        } else {
            echo " \n> \033[33m" . $message . "\033[0m";
        }
    }
}
