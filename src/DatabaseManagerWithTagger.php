<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\DatabasePartial;
use Cycle\Database\Database;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DBALException;
use Cycle\Database\LoggerFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DatabaseManagerWithTagger implements DatabaseProviderInterface, LoggerAwareInterface
{
    /** @var DatabaseWithTagger[] */
    private array $databases = [];

    /** @var DriverInterface[] */
    private array $drivers = [];

    private ?LoggerInterface $logger = null;

    public function __construct(
        private DatabaseConfig $config,
        private ?LoggerFactoryInterface $loggerFactory = null,
    ) {}

    /**
     * Set logger for all drivers
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        // Assign the logger to all initialized drivers
        foreach ($this->drivers as $driver) {
            if ($driver instanceof LoggerAwareInterface) {
                $driver->setLogger($this->logger);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function database(?string $database = null): DatabaseWithTagger
    {
        if ($database === null) {
            $database = $this->config->getDefaultDatabase();
        }

        // Cycle support ability to link multiple virtual databases together
        // using aliases.
        $database = $this->config->resolveAlias($database);

        if (isset($this->databases[$database])) {
            return $this->databases[$database];
        }

        $this->config->hasDatabase($database) or throw new DBALException(
            "Unable to create Database, no presets for '{$database}' found",
        );

        return $this->databases[$database] = $this->makeDatabase($this->config->getDatabase($database));
    }

    /**
     * Get driver instance.
     *
     * @psalm-param non-empty-string $driver
     */
    public function driver(string $driver): DriverInterface
    {
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        $driverObject           = $this->config->getDriver($driver);
        $this->drivers[$driver] = $driverObject;

        if ($driverObject instanceof LoggerAwareInterface) {
            $logger = $this->getLoggerForDriver($driverObject);

            if (!$logger instanceof NullLogger) {
                $driverObject->setLogger($logger);
            }
        }

        return $this->drivers[$driver];
    }

    private function makeDatabase(DatabasePartial $database): DatabaseWithTagger
    {
        $cycleDatabase = new Database(
            $database->getName(),
            $database->getPrefix(),
            $this->driver($database->getDriver()),
            $database->getReadDriver() ? $this->driver($database->getReadDriver()) : null,
        );

        return new DatabaseWithTagger($cycleDatabase);
    }

    private function getLoggerForDriver(DriverInterface $driver): LoggerInterface
    {
        if (!$this->loggerFactory) {
            return $this->logger ??= new NullLogger();
        }

        return $this->loggerFactory->getLogger($driver);
    }
}
