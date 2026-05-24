<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit\Logger;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use SquidIT\Cycle\Sql\Tagger\Logger\DbLoggerFixedSize;

class DbLoggerFixedSizeTest extends TestCase
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

    #[DataProvider('writeQueryProvider')]
    public function testWriteQueryIsTrackedAsWriteSucceeds(string $query): void
    {
        $dbLoggerFixedSize = new DbLoggerFixedSize();

        $dbLoggerFixedSize->log(LogLevel::DEBUG, $query, ['elapsed' => 1]);

        self::assertSame(1, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(0, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [$query],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }

    #[DataProvider('writeQueryProvider')]
    public function testCommentedWriteQueryIsTrackedAsWriteSucceeds(string $query): void
    {
        $commentedQuery    = '/* 2026-05-24T12:34:56.000000: logger test */' . "\n" . $query;
        $dbLoggerFixedSize = new DbLoggerFixedSize();

        $dbLoggerFixedSize->log(LogLevel::DEBUG, $commentedQuery, ['elapsed' => 1]);

        self::assertSame(1, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(0, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [$commentedQuery],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }

    public function testReadQueryIsTrackedAsReadSucceeds(): void
    {
        $query             = "SELECT * FROM `tag_table`";
        $dbLoggerFixedSize = new DbLoggerFixedSize();

        $dbLoggerFixedSize->log(LogLevel::DEBUG, $query, ['elapsed' => 1]);

        self::assertSame(0, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(1, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [$query],
                'write' => [],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }

    public function testQueryWithoutElapsedContextIsIgnoredSucceeds(): void
    {
        $dbLoggerFixedSize = new DbLoggerFixedSize();

        $dbLoggerFixedSize->log(LogLevel::DEBUG, "SELECT * FROM `tag_table`");

        self::assertSame(0, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(0, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }

    public function testResetClearsTrackedQueriesAndCountsSucceeds(): void
    {
        $dbLoggerFixedSize = new DbLoggerFixedSize();

        $dbLoggerFixedSize->log(LogLevel::DEBUG, "SELECT * FROM `tag_table`", ['elapsed' => 1]);
        $dbLoggerFixedSize->log(LogLevel::DEBUG, "INSERT INTO `tag_table` (`name`) VALUES ('Ada')", ['elapsed' => 1]);
        $dbLoggerFixedSize->reset();

        self::assertSame(0, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(0, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [],
                'write' => [],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }

    public function testQueryListKeepsConfiguredMaximumSizeSucceeds(): void
    {
        $firstQuery        = "SELECT * FROM `tag_table` WHERE `id` = 1";
        $secondQuery       = "SELECT * FROM `tag_table` WHERE `id` = 2";
        $dbLoggerFixedSize = new DbLoggerFixedSize(1);

        $dbLoggerFixedSize->log(LogLevel::DEBUG, $firstQuery, ['elapsed' => 1]);
        $dbLoggerFixedSize->log(LogLevel::DEBUG, $secondQuery, ['elapsed' => 1]);

        self::assertSame(0, $dbLoggerFixedSize->getWriteCount());
        self::assertSame(2, $dbLoggerFixedSize->getReadCount());
        self::assertSame(
            [
                'read'  => [$secondQuery],
                'write' => [],
            ],
            $dbLoggerFixedSize->getQueries(),
        );
    }
}
