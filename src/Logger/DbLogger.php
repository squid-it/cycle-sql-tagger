<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;

class DbLogger implements LoggerInterface
{
    use LoggerTrait;

    private bool $display = false;
    private int $countWrites;
    private int $countReads;
    /** @var array<int, string> */
    private array $readQueries = [];
    /** @var array<int, string> */
    private array $writeQueries = [];

    public function __construct()
    {
        $this->countWrites = 0;
        $this->countReads  = 0;
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
            'read'  => $this->readQueries,
            'write' => $this->writeQueries,
        ];
    }

    public function reset(): void
    {
        $this->readQueries  = [];
        $this->writeQueries = [];
        $this->countWrites  = 0;
        $this->countReads   = 0;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!empty($context['elapsed'])) {
            $sql = strtolower((string) $message);

            if (
                str_starts_with($sql, 'insert')
                || str_starts_with($sql, 'update')
                || str_starts_with($sql, 'delete')
            ) {
                $this->countWrites++;
                $this->writeQueries[] = (string) $message;
            } else {
                $this->countReads++;
                $this->readQueries[] = (string) $message;
            }
        }

        if (!$this->display) {
            return;
        }

        if ($level === LogLevel::ERROR) {
            echo " \n! \033[31m" . $message . "\033[0m";
        } elseif ($level === LogLevel::ALERT) {
            echo " \n! \033[35m" . $message . "\033[0m";
        } elseif (str_starts_with((string) $message, 'SHOW')) {
            echo " \n> \033[34m" . $message . "\033[0m";
        } elseif (str_starts_with((string) $message, 'SELECT')) {
            echo " \n> \033[32m" . $message . "\033[0m";
        } elseif (str_starts_with((string) $message, 'INSERT')) {
            echo " \n> \033[36m" . $message . "\033[0m";
        } else {
            echo " \n> \033[33m" . $message . "\033[0m";
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
}
