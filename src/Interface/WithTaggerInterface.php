<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Interface;

interface WithTaggerInterface
{
    /**
     * @param array<int, string>|string $comment Single line or multi-line comment to be added to the next query
     */
    public function tagQueryWithComment(array|string $comment): static;
}
