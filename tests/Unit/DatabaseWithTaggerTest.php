<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\MySQL\Query\MySQLDeleteQuery;
use Cycle\Database\TableInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SquidIT\Cycle\Sql\Tagger\DatabaseWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\MySQLTagDriver;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\InsertQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLDeleteQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLSelectQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLUpdateQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Exception\NotImplemented;
use SquidIT\Cycle\Sql\Tagger\Exception\SqlTaggerException;
use SquidIT\Tests\Cycle\Sql\Tagger\Database\DatabaseConfig;
use Throwable;

class DatabaseWithTaggerTest extends TestCase
{
    private const string TAG          = 'I Am A Tag';
    private const string QUERY_STRING = "SELECT 'I Am A SQL Query'";

    private DatabaseInterface&MockObject $database;

    /**
     * @throws Throwable
     */
    public function setUp(): void
    {
        $this->database = $this->createMock(DatabaseInterface::class);
    }

    public function testAddTagToQuerySucceeds(): void
    {
        $this->database
            ->expects(self::once())
            ->method('query')
            ->with(self::stringStartsWith('/*'));

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $db->query(self::QUERY_STRING);
    }

    public function testAddMultiLineTagToQuerySucceeds(): void
    {
        $this->database
            ->expects(self::once())
            ->method('query')
            ->with(self::stringStartsWith('/*' . "\n"));

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment([self::TAG, ' ', self::TAG]);
        $db->query(self::QUERY_STRING);
    }

    public function testAddingNoTagDoesNotImpactQuery(): void
    {
        $this->database
            ->expects(self::once())
            ->method('query')
            ->with(self::QUERY_STRING);

        $db = new DatabaseWithTagger($this->database);
        $db->query(self::QUERY_STRING);
    }

    public function testAddTagToExecuteSucceeds(): void
    {
        $this->database
            ->expects(self::once())
            ->method('execute')
            ->with(self::stringStartsWith('/*'));

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $db->execute(self::QUERY_STRING);
    }

    public function testAddingNoTagToSelectQueryDoesNotImpactQuery(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $selectQuery    = new MySQLSelectQueryWithTagger([$sqlTable], ['id']);
        $selectQuery    = $selectQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('select')
            ->with('*')
            ->willReturn($selectQuery);

        $db          = new DatabaseWithTagger($this->database);
        $selectQuery = $db->select();
        $sql         = $selectQuery->sqlStatement();

        self::assertStringStartsWith('SELECT ', $sql);
        self::assertStringContainsString('FROM `' . $sqlTable . '`', $sql);
    }

    public function testAddTagToSelectQuerySucceeds(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $selectQuery    = new MySQLSelectQueryWithTagger([$sqlTable], ['id']);
        $selectQuery    = $selectQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('select')
            ->with('*')
            ->willReturn($selectQuery);

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $selectQuery = $db->select();
        $sql         = $selectQuery->sqlStatement();

        self::assertStringStartsWith('/* ', $sql);
        self::assertStringContainsString(self::TAG, $sql);
        self::assertStringContainsString('FROM `' . $sqlTable . '`', $sql);
    }

    public function testAddTagToInsertQuerySucceeds(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $insertQuery    = new InsertQueryWithTagger($sqlTable);
        $insertQuery    = $insertQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('insert')
            ->with($sqlTable)
            ->willReturn($insertQuery);

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $insertQuery = $db->insert($sqlTable);
        $sql         = $insertQuery->sqlStatement();

        self::assertStringStartsWith('/* ', $sql);
        self::assertStringContainsString(self::TAG, $sql);
        self::assertStringContainsString('INSERT INTO `' . $sqlTable . '`', $sql);
    }

    public function testAddTagToUpdateQuerySucceeds(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $updateQuery    = new MySQLUpdateQueryWithTagger($sqlTable);
        $updateQuery    = $updateQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('update')
            ->with($sqlTable)
            ->willReturn($updateQuery);

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $updateQuery = $db->update($sqlTable);
        $sql         = $updateQuery->sqlStatement();

        self::assertStringStartsWith('/* ', $sql);
        self::assertStringContainsString(self::TAG, $sql);
        self::assertStringContainsString('UPDATE `' . $sqlTable . '`', $sql);
    }

    public function testAddTagToDeleteQuerySucceeds(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $deleteQuery    = new MySQLDeleteQueryWithTagger($sqlTable);
        $deleteQuery    = $deleteQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('delete')
            ->with($sqlTable)
            ->willReturn($deleteQuery);

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $deleteQuery = $db->delete($sqlTable);
        $sql         = $deleteQuery->sqlStatement();

        self::assertStringStartsWith('/* ', $sql);
        self::assertStringContainsString(self::TAG, $sql);
        self::assertStringContainsString('DELETE FROM `' . $sqlTable . '`', $sql);
    }

    public function testAddTagOnNonSupportedQueryInterfaceThrowsException(): void
    {
        $sqlTable       = 'tag_table';
        $mySqlTagDriver = MySQLTagDriver::create(DatabaseConfig::getDriverConfig());
        $deleteQuery    = new MySQLDeleteQuery($sqlTable);
        $deleteQuery    = $deleteQuery->withDriver($mySqlTagDriver);

        $this->database
            ->expects(self::once())
            ->method('delete')
            ->with($sqlTable)
            ->willReturn($deleteQuery);

        $this->expectException(SqlTaggerException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to tag SQL Query, received non supported query interface: %s', MySQLDeleteQuery::class)
        );

        $db = new DatabaseWithTagger($this->database);
        $db->tagQueryWithComment(self::TAG);
        $db->delete($sqlTable);
    }

    public function testGetNameReturnsName(): void
    {
        $name = 'name';

        $this->database
            ->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $db = new DatabaseWithTagger($this->database);
        self::assertSame($name, $db->getName());
    }

    public function testGetTypeReturnsType(): void
    {
        $type = 'type';

        $this->database
            ->expects(self::once())
            ->method('getType')
            ->willReturn($type);

        $db = new DatabaseWithTagger($this->database);
        self::assertSame($type, $db->getType());
    }

    /**
     * @throws Throwable
     */
    public function testGetDriverReturnsDriver(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $this->database
            ->expects(self::once())
            ->method('getDriver')
            ->willReturn($driver);

        $db = new DatabaseWithTagger($this->database);
        self::assertSame($driver, $db->getDriver());
    }

    public function testWithPrefixThrowsException(): void
    {
        $prefix = 'tag_';

        $this->expectException(NotImplemented::class);
        $this->expectExceptionMessage('withPrefix method not implemented');

        $db = new DatabaseWithTagger($this->database);
        $db->withPrefix($prefix);
    }

    public function testGetPrefixReturnsPrefix(): void
    {
        $prefix = 'tag_';

        $this->database
            ->expects(self::once())
            ->method('getPrefix')
            ->willReturn($prefix);

        $db = new DatabaseWithTagger($this->database);
        self::assertSame($prefix, $db->getPrefix());
    }

    public function testHasTableReturnsBool(): void
    {
        $table = 'table';

        $this->database
            ->expects(self::once())
            ->method('hasTable')
            ->with($table)
            ->willReturn(true);

        $db = new DatabaseWithTagger($this->database);
        self::assertTrue($db->hasTable($table));
    }

    public function testGetTableSReturnsLIstOfTables(): void
    {
        $tables = ['table1', 'table2'];

        $this->database
            ->expects(self::once())
            ->method('getTables')
            ->willReturn($tables);

        $db = new DatabaseWithTagger($this->database);
        self::assertSame($tables, $db->getTables());
    }

    /**
     * @throws Throwable
     */
    public function testTableReturnsTableInterface(): void
    {
        $tableName = 'table';

        $db = new DatabaseWithTagger($this->database);
        self::assertInstanceOf(TableInterface::class, $db->table($tableName));
    }

    /**
     * @throws Throwable
     */
    public function testTableMagicMethodReturnsTableInterface(): void
    {
        $table     = $this->createMock(TableInterface::class);
        $tableName = 'table';

        $this->database
            ->expects(self::once())
            ->method('table')
            ->with($tableName)
            ->willReturn($table);

        $db = new DatabaseWithTagger($this->database);

        /** @phpstan-ignore property.notFound */
        self::assertSame($table, $db->{$tableName});
    }

    /**
     * @throws Throwable
     */
    public function testCallingTransactionReturnsCallableResult(): void
    {
        $callable       = function (): void {};
        $isolationLevel = 'level1';

        $this->database
            ->expects(self::once())
            ->method('transaction')
            ->with($callable, $isolationLevel)
            ->willReturn(true);

        $db = new DatabaseWithTagger($this->database);
        $db->transaction($callable, $isolationLevel);
    }

    /**
     * @throws Throwable
     */
    public function testBeginStartsTransaction(): void
    {
        $isolationLevel = 'level1';
        $driver         = $this->createMock(DriverInterface::class);

        $driver
            ->expects(self::once())
            ->method('beginTransaction')
            ->with($isolationLevel)
            ->willReturn(true);

        $this->database
            ->expects(self::once())
            ->method('getDriver')
            ->with(DatabaseInterface::WRITE)
            ->willReturn($driver);

        $db = new DatabaseWithTagger($this->database);
        self::assertTrue($db->begin($isolationLevel));
    }

    /**
     * @throws Throwable
     */
    public function testCommitTransactionSucceeds(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects(self::once())
            ->method('commitTransaction')
            ->willReturn(true);

        $this->database
            ->expects(self::once())
            ->method('getDriver')
            ->with(DatabaseInterface::WRITE)
            ->willReturn($driver);

        $db = new DatabaseWithTagger($this->database);
        self::assertTrue($db->commit());
    }

    /**
     * @throws Throwable
     */
    public function testRollbackTransactionSucceeds(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects(self::once())
            ->method('rollbackTransaction')
            ->willReturn(true);

        $this->database
            ->expects(self::once())
            ->method('getDriver')
            ->with(DatabaseInterface::WRITE)
            ->willReturn($driver);

        $db = new DatabaseWithTagger($this->database);
        self::assertTrue($db->rollback());
    }
}
