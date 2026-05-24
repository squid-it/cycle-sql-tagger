<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger\Factory;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use SquidIT\Cycle\Sql\Tagger\Logger\DbLoggerFixedSize;
use SquidIT\Cycle\Sql\Tagger\Logger\Interface\DbLoggerInterface;

class DbLoggerFactory implements LoggerFactoryInterface
{
    public function getLogger(?DriverInterface $driver = null): DbLoggerInterface
    {
        return new DbLoggerFixedSize();
    }
}
