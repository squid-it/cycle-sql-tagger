<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Trait;

use DateTimeImmutable;
use DateTimeZone;

trait WithTaggerTrait
{
    /** @var array<int, string>|null */
    protected ?array $comment = null;

    /**
     * @param array<int|string, string>|string $comment
     */
    public function tagQueryWithComment(array|string $comment): static
    {
        if (is_string($comment)) {
            $comment = [$comment];
        }

        $finalComment = [];

        foreach ($comment as $commentLine) {
            $commentLine = trim($commentLine);

            if ($commentLine === '') {
                continue;
            }

            $finalComment[] = $commentLine;
        }

        if (empty($finalComment) === false) {
            $this->comment = $finalComment;
        }

        return $this;
    }

    protected function createSqlComment(): string
    {
        if ($this->comment === null) {
            return '';
        }

        $nrOfCommentLines = count($this->comment);

        if ($nrOfCommentLines === 1) {
            $sqlComment = '/* ' . $this->getTimestamp() . ': ' . $this->comment[0] . ' */';
        } else {
            $prepend = [
                '/*',
                'Date: ' . $this->getTimestamp(),
            ];

            array_unshift($this->comment, ...$prepend);
            $this->comment[] = '*/';

            $sqlComment = implode("\n", $this->comment);
        }

        return $sqlComment . "\n";
    }

    protected function getTimestamp(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u');
    }
}
