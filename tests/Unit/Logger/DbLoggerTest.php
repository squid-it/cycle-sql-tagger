<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit\Logger;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use SquidIT\Cycle\Sql\Tagger\Logger\DbLogger;

class DbLoggerTest extends TestCase
{
    /**
     * @return array<string, array{query: string}>
     */
    public static function writeQueryProvider(): array
    {
        return [
            'insert' => [
                'query' => "INSERT INTO `tag_table` (`name`) VALUES ('Ada')",
            ],
            'update' => [
                'query' => "UPDATE `tag_table` SET `name` = 'Ada' WHERE `id` = 1",
            ],
            'delete' => [
                'query' => "DELETE FROM `tag_table` WHERE `id` = 1",
            ],
            'replace' => [
                'query' => "REPLACE INTO `tag_table` (`id`, `name`) VALUES (1, 'Ada')",
            ],
            'create' => [
                'query' => "CREATE TABLE `tag_table` (`id` INT NOT NULL)",
            ],
            'alter' => [
                'query' => "ALTER TABLE `tag_table` ADD COLUMN `name` VARCHAR(255)",
            ],
            'drop' => [
                'query' => "DROP TABLE `tag_table`",
            ],
            'truncate' => [
                'query' => "TRUNCATE TABLE `tag_table`",
            ],
            'rename' => [
                'query' => "RENAME TABLE `tag_table` TO `tag_table_archive`",
            ],
            'merge' => [
                'query' => "MERGE INTO `tag_table` USING `tag_table_source` ON `tag_table`.`id` = `tag_table_source`.`id`",
            ],
            'load data' => [
                'query' => "LOAD DATA LOCAL INFILE 'tag_table.csv' INTO TABLE `tag_table`",
            ],
        ];
    }

    /**
     * @return array<string, array{query: string, expectedColor: string}>
     */
    public static function displayQueryColorProvider(): array
    {
        return [
            'show' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nSHOW TABLES",
                'expectedColor' => "\033[34m",
            ],
            'select' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nSELECT * FROM `tag_table`",
                'expectedColor' => "\033[32m",
            ],
            'insert' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nINSERT INTO `tag_table` (`name`) VALUES ('Ada')",
                'expectedColor' => "\033[36m",
            ],
            'update' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nUPDATE `tag_table` SET `name` = 'Ada'",
                'expectedColor' => "\033[33m",
            ],
        ];
    }

    #[DataProvider('writeQueryProvider')]
    public function testWriteQueryIsTrackedAsWriteSucceeds(string $query): void
    {
        $dbLogger = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, $query, ['elapsed' => 1]);

        self::assertSame(1, $dbLogger->getWriteCount());
        self::assertSame(0, $dbLogger->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [$query],
            ],
            $dbLogger->getQueries(),
        );
    }

    #[DataProvider('writeQueryProvider')]
    public function testCommentedWriteQueryIsTrackedAsWriteSucceeds(string $query): void
    {
        $commentedQuery = '/* 2026-05-24T12:34:56.000000: logger test */' . "\n" . $query;
        $dbLogger       = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, $commentedQuery, ['elapsed' => 1]);

        self::assertSame(1, $dbLogger->getWriteCount());
        self::assertSame(0, $dbLogger->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [$commentedQuery],
            ],
            $dbLogger->getQueries(),
        );
    }

    public function testReadQueryIsTrackedAsReadSucceeds(): void
    {
        $query    = 'SELECT * FROM `tag_table`';
        $dbLogger = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, $query, ['elapsed' => 1]);

        self::assertSame(0, $dbLogger->getWriteCount());
        self::assertSame(1, $dbLogger->getReadCount());
        self::assertSame(
            [
                'read'  => [$query],
                'write' => [],
            ],
            $dbLogger->getQueries(),
        );
    }

    public function testQueryWithoutElapsedContextIsIgnoredSucceeds(): void
    {
        $dbLogger = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, 'SELECT * FROM `tag_table`');

        self::assertSame(0, $dbLogger->getWriteCount());
        self::assertSame(0, $dbLogger->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [],
            ],
            $dbLogger->getQueries(),
        );
    }

    public function testResetClearsTrackedQueriesAndCountsSucceeds(): void
    {
        $dbLogger = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, 'SELECT * FROM `tag_table`', ['elapsed' => 1]);
        $dbLogger->log(LogLevel::DEBUG, "INSERT INTO `tag_table` (`name`) VALUES ('Ada')", ['elapsed' => 1]);
        $dbLogger->reset();

        self::assertSame(0, $dbLogger->getWriteCount());
        self::assertSame(0, $dbLogger->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [],
            ],
            $dbLogger->getQueries(),
        );
    }

    #[DataProvider('displayQueryColorProvider')]
    public function testDisplayUsesSqlAfterCommentForColorSucceeds(string $query, string $expectedColor): void
    {
        $dbLogger = new DbLogger();
        $dbLogger->enableDisplay();

        self::assertTrue(ob_start());

        $dbLogger->log(LogLevel::DEBUG, $query, ['elapsed' => 1]);

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringStartsWith(" \n> " . $expectedColor, $output);
        self::assertStringContainsString($query, $output);
    }
}
