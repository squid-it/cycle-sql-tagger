<?php

declare(strict_types=1);

namespace SquidIT\Cycle\Sql\Tagger\Tag;

class CreateComment
{
    /**
     * @return array<int, string>
     */
    public static function sql(string $filename, int $lineNumber, ?string $functionName): array
    {
        $sqlComment = [
            'File: ' . $filename,
            'Line: ' . $lineNumber,
        ];

        if ($functionName !== null) {
            $sqlComment[] = 'Function: ' . $functionName;
        }

        return $sqlComment;
    }
}
