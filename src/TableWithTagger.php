<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger;

use Countable;
use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\IndexInterface;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\TableInterface;
use IteratorAggregate;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\InsertQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLDeleteQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLSelectQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLUpdateQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Exception\SqlTaggerException;

/**
 * Represent table level abstraction with simplified access to SelectQuery associated with selected table.
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 */
class TableWithTagger implements TableInterface, IteratorAggregate, Countable
{
    /**
     * @psalm-param non-empty-string $name Table name without prefix.
     */
    public function __construct(
        protected DatabaseWithTagger $database,
        private string $name,
    ) {}

    /**
     * @param array<int|string, string>|string $comment
     */
    public function tagQueryWithComment(array|string $comment): static
    {
        $this->database->tagQueryWithComment($comment);

        return $this;
    }

    /**
     * Get an associated database.
     */
    public function getDatabase(): DatabaseWithTagger
    {
        return $this->database;
    }

    /**
     * Real table name will include database prefix.
     *
     * @psalm-return non-empty-string
     */
    public function getFullName(): string
    {
        return $this->database->getPrefix() . $this->name;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get modifiable table schema.
     */
    public function getSchema(): AbstractTable
    {
        return $this->database
            ->getDriver(DatabaseInterface::WRITE)
            ->getSchemaHandler()
            ->getSchema(
                $this->name,
                $this->database->getPrefix(),
            );
    }

    /**
     * Erase all table data.
     */
    public function eraseData(): void
    {
        $this->database
            ->getDriver(DatabaseInterface::WRITE)
            ->getSchemaHandler()
            ->eraseTable($this->getSchema());
    }

    /**
     * Insert one fieldset into table and return the last inserted id.
     *
     * Example:
     * $table->insertOne(["name" => "Wolfy-J", "balance" => 10]);
     *
     * @throws BuilderException
     * @throws SqlTaggerException
     */
    public function insertOne(array $rowSet = []): int|string|null
    {
        /** @phpstan-ignore return.type */
        return $this->database
            ->insert($this->name)
            ->values($rowSet)
            ->run();
    }

    /**
     * Perform batch insert into table, every rowset should have identical amount of values matched
     * with column names provided in first argument. Method will return lastInsertID on success.
     *
     * Example:
     * $table->insertMultiple(["name", "balance"], array(["Bob", 10], ["Jack", 20]))
     *
     * @param array $columns Array of columns.
     * @param array $rowSets Array of rowsets.
     *
     * @throws SqlTaggerException
     */
    public function insertMultiple(array $columns = [], array $rowSets = []): void
    {
        // No return value
        $this->database
            ->insert($this->name)
            ->columns($columns)
            ->values($rowSets)
            ->run();
    }

    /**
     * Get insert builder specific to current table.
     *
     * @throws SqlTaggerException
     */
    public function insert(): InsertQueryWithTagger
    {
        return $this->database
            ->insert($this->name);
    }

    /**
     * Get SelectQuery builder with pre-populated from tables.
     *
     * @throws SqlTaggerException
     */
    public function select(mixed $columns = '*'): MySQLSelectQueryWithTagger
    {
        /** @phpstan-ignore return.type */
        return $this->database
            ->select(\func_num_args() ? \func_get_args() : '*')
            ->from($this->name);
    }

    /**
     * Get DeleteQuery builder with pre-populated table name. This is NOT table delete method, use
     * schema()->drop() for this purpose. If you want to remove all records from table use
     * Table->truncate() method. Call ->run() to execute a query.
     *
     * @param array $where Initial set of where rules specified as an array.
     *
     * @throws SqlTaggerException
     */
    public function delete(array $where = []): MySQLDeleteQueryWithTagger
    {
        return $this->database
            ->delete($this->name, $where);
    }

    /**
     * Get UpdateQuery builder with pre-populated table name and set of columns to update. Columns
     * can be scalar values, Parameter objects or even SQLFragments. Call ->run() to perform query.
     *
     * @param array $values Initial set of columns associated with values.
     * @param array $where  Initial set of where rules specified as array.
     *
     * @throws SqlTaggerException
     */
    public function update(array $values = [], array $where = []): MySQLUpdateQueryWithTagger
    {
        return $this->database
            ->update($this->name, $values, $where);
    }

    /**
     * Count number of records in table.
     *
     * @throws SqlTaggerException
     */
    public function count(): int
    {
        return $this->select()->count();
    }

    /**
     * Retrieve an external iterator, SelectBuilder will return PDOResult as iterator.
     *
     * @see http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @throws SqlTaggerException
     */
    public function getIterator(): MySQLSelectQueryWithTagger
    {
        return $this->select();
    }

    /**
     * A simple alias for a table query without condition (return array of rows).
     *
     * @throws SqlTaggerException
     */
    public function fetchAll(): array
    {
        return $this->select()->fetchAll();
    }

    public function exists(): bool
    {
        return $this->getSchema()->exists();
    }

    /**
     * Array of columns dedicated to primary index. Attention, this method will ALWAYS return
     * an array, even if there is only one primary key.
     */
    public function getPrimaryKeys(): array
    {
        return $this->getSchema()->getPrimaryKeys();
    }

    /**
     * Check if table has specified column.
     *
     * @psalm-param non-empty-string $name Column name.
     */
    public function hasColumn(string $name): bool
    {
        return $this->getSchema()->hasColumn($name);
    }

    /**
     * Get all declared columns.
     *
     * @return ColumnInterface[]
     */
    public function getColumns(): array
    {
        return $this->getSchema()->getColumns();
    }

    /**
     * Check if table has index related to set of provided columns. Column order does matter!
     */
    public function hasIndex(array $columns = []): bool
    {
        return $this->getSchema()->hasIndex($columns);
    }

    /**
     * Get all table indexes.
     *
     * @return IndexInterface[]
     */
    public function getIndexes(): array
    {
        return $this->getSchema()->getIndexes();
    }

    /**
     * Check if table has foreign key related to table column.
     *
     * @param array $columns Column names.
     */
    public function hasForeignKey(array $columns): bool
    {
        return $this->getSchema()->hasForeignKey($columns);
    }

    /**
     * Get all table foreign keys.
     *
     * @return ForeignKeyInterface[]
     */
    public function getForeignKeys(): array
    {
        return $this->getSchema()->getForeignKeys();
    }

    /**
     * Get a list of table names current schema depends on, must include every table linked using
     * foreign key or other constraint. Table names MUST include prefixes.
     */
    public function getDependencies(): array
    {
        return $this->getSchema()->getDependencies();
    }

    /**
     * Bypass call to SelectQuery builder.
     *
     * @psalm-param non-empty-string $method
     *
     * @throws SqlTaggerException
     *
     * @return mixed|SelectQuery
     */
    public function __call(string $method, array $arguments): mixed
    {
        /** @phpstan-ignore argument.type */
        return \call_user_func_array([$this->select(), $method], $arguments);
    }
}
