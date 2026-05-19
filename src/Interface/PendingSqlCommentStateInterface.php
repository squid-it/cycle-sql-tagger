<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Interface;

interface PendingSqlCommentStateInterface
{
    public function hasPendingSqlComment(): bool;

    public function clearPendingSqlComment(): void;
}
