<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\StatementInterface;
use Cycle\Database\Table;
use DateTimeInterface;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\InsertQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLDeleteQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLSelectQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Driver\MySQL\Query\MySQLUpdateQueryWithTagger;
use SquidIT\Cycle\Sql\Tagger\Exception\NotImplemented;
use SquidIT\Cycle\Sql\Tagger\Exception\SqlTaggerException;
use SquidIT\Cycle\Sql\Tagger\Interface\WithTaggerInterface;
use SquidIT\Cycle\Sql\Tagger\Trait\WithTaggerTrait;

class DatabaseWithTagger implements DatabaseInterface, WithTaggerInterface
{
    use WithTaggerTrait;

    private const array QUERY_WITH_TAGGER_CLASSES = [
        MySQLSelectQueryWithTagger::class,
        MySQLUpdateQueryWithTagger::class,
        InsertQueryWithTagger::class,
        MySQLDeleteQueryWithTagger::class,
    ];

    public function __construct(
        private DatabaseInterface $db,
    ) {}

    public function getName(): string
    {
        return $this->db->getName();
    }

    public function getType(): string
    {
        return $this->db->getType();
    }

    public function getDriver(int $type = self::WRITE): DriverInterface
    {
        return $this->db->getDriver($type);
    }

    /**
     * @throws NotImplemented
     */
    public function withPrefix(string $prefix, bool $add = true): DatabaseInterface
    {
        throw new NotImplemented('withPrefix method not implemented');
    }

    public function getPrefix(): string
    {
        return $this->db->getPrefix();
    }

    public function hasTable(string $name): bool
    {
        return $this->db->hasTable($name);
    }

    public function getTables(): array
    {
        return $this->db->getTables();
    }

    public function table(string $name): TableWithTagger
    {
        return new TableWithTagger($this, $name);
    }

    /**
     * @param array<string, bool|DateTimeInterface|float|int|string|null> $parameters
     */
    public function execute(string $query, array $parameters = []): int
    {
        $query         = $this->createSqlComment() . $query;
        $this->comment = null;

        return $this->db->execute($query, $parameters);
    }

    /**
     * @param array<string, bool|DateTimeInterface|float|int|string|null> $parameters
     */
    public function query(string $query, array $parameters = []): StatementInterface
    {
        $query         = $this->createSqlComment() . $query;
        $this->comment = null;

        return $this->db->query($query, $parameters);
    }

    /**
     * @throws SqlTaggerException
     */
    public function insert(string $table = ''): InsertQueryWithTagger
    {
        /** @var InsertQueryWithTagger $insertQuery */
        $insertQuery = $this->db->insert($table);

        return $this->addTag($insertQuery);
    }

    /**
     * @throws SqlTaggerException
     */
    public function update(string $table = '', array $values = [], array $where = []): MySQLUpdateQueryWithTagger
    {
        /** @var MySQLUpdateQueryWithTagger $updateQuery */
        $updateQuery = $this->db->update($table, $values, $where);

        return $this->addTag($updateQuery);
    }

    /**
     * @throws SqlTaggerException
     */
    public function delete(string $table = '', array $where = []): MySQLDeleteQueryWithTagger
    {
        /** @var MySQLDeleteQueryWithTagger $deleteQuery */
        $deleteQuery = $this->db->delete($table, $where);

        return $this->addTag($deleteQuery);
    }

    /**
     * @throws SqlTaggerException
     */
    public function select($columns = '*'): MySQLSelectQueryWithTagger
    {
        /** @var MySQLSelectQueryWithTagger $selectQuery */
        $selectQuery = $this->db->select($columns);

        return $this->addTag($selectQuery);
    }

    public function transaction(
        callable $callback,
        ?string $isolationLevel = null,
    ): mixed {
        return $this->db->transaction($callback, $isolationLevel);
    }

    public function begin(?string $isolationLevel = null): bool
    {
        return $this->db->getDriver(self::WRITE)->beginTransaction($isolationLevel);
    }

    public function commit(): bool
    {
        return $this->db->getDriver(self::WRITE)->commitTransaction();
    }

    public function rollback(): bool
    {
        return $this->db->getDriver(self::WRITE)->rollbackTransaction();
    }

    /**
     * Shortcut to get table abstraction.
     *
     * @psalm-param non-empty-string $name Table name without prefix.
     */
    public function __get(string $name): TableWithTagger
    {
        return $this->table($name);
    }

    /**
     * @template T of QueryInterface
     *
     * @phpstan-param T $query
     *
     * @phpstan-return T
     *
     * @throws SqlTaggerException
     */
    private function addTag(QueryInterface $query): QueryInterface
    {
        if ($this->comment === null) {
            return $query;
        }

        $queryClassType = get_class($query);

        if (in_array($queryClassType, self::QUERY_WITH_TAGGER_CLASSES, true) === false) {
            throw new SqlTaggerException(
                'Unable to tag SQL Query, received non supported query interface: ' . $queryClassType
            );
        }

        /** @var InsertQueryWithTagger|MySQLDeleteQueryWithTagger|MySQLSelectQueryWithTagger|MySQLUpdateQueryWithTagger $query */
        $query->tagQueryWithComment($this->comment);
        $this->comment = null;

        return $query;
    }
}
