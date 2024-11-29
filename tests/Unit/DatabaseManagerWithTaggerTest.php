<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit;

use Cycle\Database\LoggerFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SquidIT\Cycle\Sql\Tagger\DatabaseManagerWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\MySQLTagDriver;
use SquidIT\Tests\Cycle\Sql\Tagger\Database\DatabaseConfig;
use Throwable;

class DatabaseManagerWithTaggerTest extends TestCase
{
    private const string DEFAULT_DATABASE_NAME = 'mariaDb';
    private const string DRIVER_NAME           = 'mariaDbDsn';

    private LoggerFactoryInterface&MockObject $loggerFactory;

    /**
     * @throws Throwable
     */
    public function setUp(): void
    {
        $this->loggerFactory = $this->createMock(LoggerFactoryInterface::class);
    }

    public function testDatabaseReturnsDefaultDatabase(): void
    {
        $dbal     = new DatabaseManagerWithTagger(DatabaseConfig::get());
        $database = $dbal->database();

        self::assertSame(self::DEFAULT_DATABASE_NAME, $database->getName());
    }

    public function testFetchingDatabaseMultipleTimesReturnsDatabaseFromCache(): void
    {
        $dbal      = new DatabaseManagerWithTagger(DatabaseConfig::get());
        $database1 = $dbal->database(self::DEFAULT_DATABASE_NAME);
        $database2 = $dbal->database(self::DEFAULT_DATABASE_NAME);

        self::assertSame($database1, $database2);
    }

    public function testDatabaseReturnsDatabaseWithLoggerWhenLoggerFactoryIsProvided(): void
    {
        $nullLogger = new NullLogger();

        $this->loggerFactory
            ->expects(self::once())
            ->method('getLogger')
            ->willReturn($nullLogger);

        $dbal = new DatabaseManagerWithTagger(DatabaseConfig::get(), $this->loggerFactory);
        $dbal->database();
    }

    public function testDriverReturnsDriverInstanceFromCacheDriverWasPreviouslyLoaded(): void
    {
        $dbal = new DatabaseManagerWithTagger(DatabaseConfig::get());
        $dbal->driver(self::DRIVER_NAME);

        self::assertInstanceOf(MySQLTagDriver::class, $dbal->driver(self::DRIVER_NAME));
    }

    public function testSetLoggerWillSetLoggerOnPreviouslyDefinedDrivers(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $dbal = new DatabaseManagerWithTagger(DatabaseConfig::get());
        $dbal->driver(self::DRIVER_NAME);

        $dbal->setLogger($logger);

        self::assertInstanceOf(MySQLTagDriver::class, $dbal->driver(self::DRIVER_NAME));
    }

    /**
     * @throws Throwable
     */
    public function testSetLoggerWillSetLoggerOnInitialDriverFetch(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $dbal = new DatabaseManagerWithTagger(DatabaseConfig::get());
        $dbal->setLogger($logger);
        $dbal->driver(self::DRIVER_NAME);

        self::assertInstanceOf(MySQLTagDriver::class, $dbal->driver(self::DRIVER_NAME));
    }
}
