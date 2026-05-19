<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Logger;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DbLoggerFactory implements LoggerFactoryInterface
{
    public function getLogger(?DriverInterface $driver = null): LoggerInterface
    {
        return new DbLogger();
        // return new NullLogger();
    }
}
