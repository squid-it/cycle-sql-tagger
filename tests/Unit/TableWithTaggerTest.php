<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit;

use Countable;
use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\IndexInterface;
use Cycle\Database\Schema\AbstractTable;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SquidIT\Cycle\Sql\Tagger\DatabaseWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\InsertQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLDeleteQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLSelectQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLUpdateQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\TableWithTagger;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
class TableWithTaggerTest extends TestCase
{
    private const string TABLE_NAME   = 'tag_table';
    private const string TABLE_PREFIX = 'app_';

    private DatabaseWithTagger&MockObject $databaseWithTagger;
    private TableWithTagger $tableWithTagger;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseWithTagger = $this->createMock(DatabaseWithTagger::class);
        $this->tableWithTagger    = new TableWithTagger($this->databaseWithTagger, self::TABLE_NAME);
    }

    public function testImplementsExpectedInterfacesSucceeds(): void
    {
        self::assertInstanceOf(Countable::class, $this->tableWithTagger);
        self::assertInstanceOf(IteratorAggregate::class, $this->tableWithTagger);
    }

    public function testTagQueryWithCommentReturnsSelfSucceeds(): void
    {
        $commentLine = 'comment';

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('tagQueryWithComment')
            ->with($commentLine)
            ->willReturn($this->databaseWithTagger);

        self::assertSame($this->tableWithTagger, $this->tableWithTagger->tagQueryWithComment($commentLine));
    }

    public function testGetDatabaseReturnsDatabaseSucceeds(): void
    {
        self::assertSame($this->databaseWithTagger, $this->tableWithTagger->getDatabase());
    }

    public function testGetFullNameReturnsPrefixedNameSucceeds(): void
    {
        $this->databaseWithTagger
            ->expects(self::once())
            ->method('getPrefix')
            ->willReturn(self::TABLE_PREFIX);

        self::assertSame(self::TABLE_PREFIX . self::TABLE_NAME, $this->tableWithTagger->getFullName());
    }

    public function testGetNameReturnsTableNameSucceeds(): void
    {
        self::assertSame(self::TABLE_NAME, $this->tableWithTagger->getName());
    }

    /**
     * @throws Throwable
     */
    public function testGetSchemaReturnsSchemaSucceeds(): void
    {
        $abstractTable = $this->expectSchemaFetch();

        self::assertSame($abstractTable, $this->tableWithTagger->getSchema());
    }

    /**
     * @throws Throwable
     */
    public function testEraseDataErasesFetchedSchemaSucceeds(): void
    {
        $driver        = $this->createMock(DriverInterface::class);
        $handler       = $this->createMock(HandlerInterface::class);
        $abstractTable = $this->createMock(AbstractTable::class);

        $this->databaseWithTagger
            ->expects(self::exactly(2))
            ->method('getDriver')
            ->with(DatabaseInterface::WRITE)
            ->willReturn($driver);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('getPrefix')
            ->willReturn(self::TABLE_PREFIX);

        $driver
            ->expects(self::exactly(2))
            ->method('getSchemaHandler')
            ->willReturn($handler);

        $handler
            ->expects(self::once())
            ->method('getSchema')
            ->with(self::TABLE_NAME, self::TABLE_PREFIX)
            ->willReturn($abstractTable);

        $handler
            ->expects(self::once())
            ->method('eraseTable')
            ->with($abstractTable);

        $this->tableWithTagger->eraseData();
    }

    /**
     * @throws Throwable
     */
    public function testInsertOneReturnsLastInsertIdSucceeds(): void
    {
        $rowSet = [
            'name' => 'Ada',
        ];

        $insertQueryWithTagger = $this->createMock(InsertQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('insert')
            ->with(self::TABLE_NAME)
            ->willReturn($insertQueryWithTagger);

        $insertQueryWithTagger
            ->expects(self::once())
            ->method('values')
            ->with($rowSet)
            ->willReturnSelf();

        $insertQueryWithTagger
            ->expects(self::once())
            ->method('run')
            ->willReturn(123);

        self::assertSame(123, $this->tableWithTagger->insertOne($rowSet));
    }

    /**
     * @throws Throwable
     */
    public function testInsertMultipleRunsInsertQuerySucceeds(): void
    {
        $columnList = [
            'name',
            'email',
        ];
        $rowSetList = [
            [
                'Ada',
                'ada@example.com',
            ],
            [
                'Grace',
                'grace@example.com',
            ],
        ];

        $insertQueryWithTagger = $this->createMock(InsertQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('insert')
            ->with(self::TABLE_NAME)
            ->willReturn($insertQueryWithTagger);

        $insertQueryWithTagger
            ->expects(self::once())
            ->method('columns')
            ->with($columnList)
            ->willReturnSelf();

        $insertQueryWithTagger
            ->expects(self::once())
            ->method('values')
            ->with($rowSetList)
            ->willReturnSelf();

        $insertQueryWithTagger
            ->expects(self::once())
            ->method('run')
            ->willReturn(123);

        $this->tableWithTagger->insertMultiple($columnList, $rowSetList);
    }

    /**
     * @throws Throwable
     */
    public function testInsertReturnsInsertQuerySucceeds(): void
    {
        $insertQueryWithTagger = $this->createMock(InsertQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('insert')
            ->with(self::TABLE_NAME)
            ->willReturn($insertQueryWithTagger);

        self::assertSame($insertQueryWithTagger, $this->tableWithTagger->insert());
    }

    /**
     * @throws Throwable
     */
    public function testSelectReturnsSelectQueryWithDefaultColumnsSucceeds(): void
    {
        $mySQLSelectQueryWithTagger = $this->expectSelectWithDefaultColumns();

        self::assertSame($mySQLSelectQueryWithTagger, $this->tableWithTagger->select());
    }

    /**
     * @throws Throwable
     */
    public function testSelectReturnsSelectQueryWithProvidedColumnsSucceeds(): void
    {
        $columnList = [
            'id',
            'name',
        ];

        $mySQLSelectQueryWithTagger = $this->createMock(MySQLSelectQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('select')
            ->with($columnList)
            ->willReturn($mySQLSelectQueryWithTagger);

        $mySQLSelectQueryWithTagger
            ->expects(self::once())
            ->method('from')
            ->with(self::TABLE_NAME)
            ->willReturnSelf();

        self::assertSame(
            $mySQLSelectQueryWithTagger,
            \call_user_func_array([$this->tableWithTagger, 'select'], $columnList),
        );
    }

    /**
     * @throws Throwable
     */
    public function testDeleteReturnsDeleteQuerySucceeds(): void
    {
        $where = [
            'id' => 123,
        ];

        $mySQLDeleteQueryWithTagger = $this->createMock(MySQLDeleteQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('delete')
            ->with(self::TABLE_NAME, $where)
            ->willReturn($mySQLDeleteQueryWithTagger);

        self::assertSame($mySQLDeleteQueryWithTagger, $this->tableWithTagger->delete($where));
    }

    /**
     * @throws Throwable
     */
    public function testUpdateReturnsUpdateQuerySucceeds(): void
    {
        $valueList = [
            'name' => 'Ada',
        ];
        $where = [
            'id' => 123,
        ];

        $mySQLUpdateQueryWithTagger = $this->createMock(MySQLUpdateQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('update')
            ->with(self::TABLE_NAME, $valueList, $where)
            ->willReturn($mySQLUpdateQueryWithTagger);

        self::assertSame($mySQLUpdateQueryWithTagger, $this->tableWithTagger->update($valueList, $where));
    }

    /**
     * @throws Throwable
     */
    public function testCountReturnsSelectCountSucceeds(): void
    {
        $mySQLSelectQueryWithTagger = $this->expectSelectWithDefaultColumns();

        $mySQLSelectQueryWithTagger
            ->expects(self::once())
            ->method('count')
            ->willReturn(7);

        self::assertSame(7, $this->tableWithTagger->count());
    }

    /**
     * @throws Throwable
     */
    public function testGetIteratorReturnsSelectQuerySucceeds(): void
    {
        $mySQLSelectQueryWithTagger = $this->expectSelectWithDefaultColumns();

        self::assertSame($mySQLSelectQueryWithTagger, $this->tableWithTagger->getIterator());
    }

    /**
     * @throws Throwable
     */
    public function testFetchAllReturnsRowsSucceeds(): void
    {
        $rowList = [
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ];

        $mySQLSelectQueryWithTagger = $this->expectSelectWithDefaultColumns();

        $mySQLSelectQueryWithTagger
            ->expects(self::once())
            ->method('fetchAll')
            ->willReturn($rowList);

        self::assertSame($rowList, $this->tableWithTagger->fetchAll());
    }

    /**
     * @throws Throwable
     */
    public function testExistsReturnsSchemaExistsSucceeds(): void
    {
        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        self::assertTrue($this->tableWithTagger->exists());
    }

    /**
     * @throws Throwable
     */
    public function testGetPrimaryKeysReturnsSchemaPrimaryKeysSucceeds(): void
    {
        $primaryKeyList = [
            'id',
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('getPrimaryKeys')
            ->willReturn($primaryKeyList);

        self::assertSame($primaryKeyList, $this->tableWithTagger->getPrimaryKeys());
    }

    /**
     * @throws Throwable
     */
    public function testHasColumnReturnsSchemaResultSucceeds(): void
    {
        $columnName = 'name';

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('hasColumn')
            ->with($columnName)
            ->willReturn(true);

        self::assertTrue($this->tableWithTagger->hasColumn($columnName));
    }

    /**
     * @throws Throwable
     */
    public function testGetColumnsReturnsSchemaColumnsSucceeds(): void
    {
        $columnList = [
            self::createStub(ColumnInterface::class),
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('getColumns')
            ->willReturn($columnList);

        self::assertSame($columnList, $this->tableWithTagger->getColumns());
    }

    /**
     * @throws Throwable
     */
    public function testHasIndexReturnsSchemaResultSucceeds(): void
    {
        $columnList = [
            'email',
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('hasIndex')
            ->with($columnList)
            ->willReturn(true);

        self::assertTrue($this->tableWithTagger->hasIndex($columnList));
    }

    /**
     * @throws Throwable
     */
    public function testGetIndexesReturnsSchemaIndexesSucceeds(): void
    {
        $indexList = [
            self::createStub(IndexInterface::class),
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('getIndexes')
            ->willReturn($indexList);

        self::assertSame($indexList, $this->tableWithTagger->getIndexes());
    }

    /**
     * @throws Throwable
     */
    public function testHasForeignKeyReturnsSchemaResultSucceeds(): void
    {
        $columnList = [
            'user_id',
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('hasForeignKey')
            ->with($columnList)
            ->willReturn(true);

        self::assertTrue($this->tableWithTagger->hasForeignKey($columnList));
    }

    /**
     * @throws Throwable
     */
    public function testGetForeignKeysReturnsSchemaForeignKeysSucceeds(): void
    {
        $foreignKeyList = [
            self::createStub(ForeignKeyInterface::class),
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('getForeignKeys')
            ->willReturn($foreignKeyList);

        self::assertSame($foreignKeyList, $this->tableWithTagger->getForeignKeys());
    }

    /**
     * @throws Throwable
     */
    public function testGetDependenciesReturnsSchemaDependenciesSucceeds(): void
    {
        $tableNameList = [
            'app_users',
        ];

        $abstractTable = $this->expectSchemaFetch();

        $abstractTable
            ->expects(self::once())
            ->method('getDependencies')
            ->willReturn($tableNameList);

        self::assertSame($tableNameList, $this->tableWithTagger->getDependencies());
    }

    /**
     * @throws Throwable
     */
    public function testCallForwardsToSelectQuerySucceeds(): void
    {
        $mySQLSelectQueryWithTagger = $this->expectSelectWithDefaultColumns();

        $mySQLSelectQueryWithTagger
            ->expects(self::once())
            ->method('avg')
            ->with('price')
            ->willReturn(42);

        self::assertSame(42, $this->tableWithTagger->__call('avg', ['price']));
    }

    /**
     * @throws Throwable
     */
    private function expectSchemaFetch(): AbstractTable&MockObject
    {
        $driver        = $this->createMock(DriverInterface::class);
        $handler       = $this->createMock(HandlerInterface::class);
        $abstractTable = $this->createMock(AbstractTable::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('getDriver')
            ->with(DatabaseInterface::WRITE)
            ->willReturn($driver);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('getPrefix')
            ->willReturn(self::TABLE_PREFIX);

        $driver
            ->expects(self::once())
            ->method('getSchemaHandler')
            ->willReturn($handler);

        $handler
            ->expects(self::once())
            ->method('getSchema')
            ->with(self::TABLE_NAME, self::TABLE_PREFIX)
            ->willReturn($abstractTable);

        return $abstractTable;
    }

    /**
     * @throws Throwable
     */
    private function expectSelectWithDefaultColumns(): MySQLSelectQueryWithTagger&MockObject
    {
        $mySQLSelectQueryWithTagger = $this->createMock(MySQLSelectQueryWithTagger::class);

        $this->databaseWithTagger
            ->expects(self::once())
            ->method('select')
            ->with('*')
            ->willReturn($mySQLSelectQueryWithTagger);

        $mySQLSelectQueryWithTagger
            ->expects(self::once())
            ->method('from')
            ->with(self::TABLE_NAME)
            ->willReturnSelf();

        return $mySQLSelectQueryWithTagger;
    }
}
