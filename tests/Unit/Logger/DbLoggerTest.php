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
                'expectedColor' => "\033[36m",
            ],
            'delete' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nDELETE FROM `tag_table` WHERE `id` = 1",
                'expectedColor' => "\033[36m",
            ],
            'replace' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nREPLACE INTO `tag_table` (`id`, `name`) VALUES (1, 'Ada')",
                'expectedColor' => "\033[36m",
            ],
            'create' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nCREATE TABLE `tag_table` (`id` INT NOT NULL)",
                'expectedColor' => "\033[36m",
            ],
            'alter' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nALTER TABLE `tag_table` ADD COLUMN `name` VARCHAR(255)",
                'expectedColor' => "\033[36m",
            ],
            'drop' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nDROP TABLE `tag_table`",
                'expectedColor' => "\033[36m",
            ],
            'truncate' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nTRUNCATE TABLE `tag_table`",
                'expectedColor' => "\033[36m",
            ],
            'rename' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nRENAME TABLE `tag_table` TO `tag_table_archive`",
                'expectedColor' => "\033[36m",
            ],
            'merge' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nMERGE INTO `tag_table` USING `tag_table_source` ON `tag_table`.`id` = `tag_table_source`.`id`",
                'expectedColor' => "\033[36m",
            ],
            'load data' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nLOAD DATA LOCAL INFILE 'tag_table.csv' INTO TABLE `tag_table`",
                'expectedColor' => "\033[36m",
            ],
            'lowercase show' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nshow tables",
                'expectedColor' => "\033[34m",
            ],
            'lowercase select' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nselect * from `tag_table`",
                'expectedColor' => "\033[32m",
            ],
            'lowercase insert' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\ninsert into `tag_table` (`name`) values ('Ada')",
                'expectedColor' => "\033[36m",
            ],
            'lowercase update' => [
                'query'         => "/* 2026-05-24T12:34:56.000000: logger test */\nupdate `tag_table` set `name` = 'Ada'",
                'expectedColor' => "\033[36m",
            ],
        ];
    }

    /**
     * @return array<string, array{query: string}>
     */
    public static function lineCommentedWriteQueryProvider(): array
    {
        return [
            'dash comment' => [
                'query' => "-- logger test\nINSERT INTO `tag_table` (`name`) VALUES ('Ada')",
            ],
            'hash comment' => [
                'query' => "# logger test\nINSERT INTO `tag_table` (`name`) VALUES ('Ada')",
            ],
            'mixed comments' => [
                'query' => "-- logger test\r\n# logger test\n/* logger test */\nUPDATE `tag_table` SET `name` = 'Ada'",
            ],
        ];
    }

    /**
     * @return array<string, array{elapsed: float|int|string}>
     */
    public static function zeroElapsedContextProvider(): array
    {
        return [
            'float zero' => [
                'elapsed' => 0.0,
            ],
            'integer zero' => [
                'elapsed' => 0,
            ],
            'string zero' => [
                'elapsed' => '0',
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

    #[DataProvider('lineCommentedWriteQueryProvider')]
    public function testLineCommentedWriteQueryIsTrackedAsWriteSucceeds(string $query): void
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

    #[DataProvider('zeroElapsedContextProvider')]
    public function testQueryWithZeroElapsedContextIsTrackedSucceeds(float|int|string $elapsed): void
    {
        $query    = 'SELECT * FROM `tag_table`';
        $dbLogger = new DbLogger();

        $dbLogger->log(LogLevel::DEBUG, $query, ['elapsed' => $elapsed]);

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
