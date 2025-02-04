<?php

declare(strict_types=1);

namespace SquidIT\Tests\Cycle\Sql\Tagger\Unit\Tag;

use PHPUnit\Framework\TestCase;
use SquidIT\Cycle\Sql\Tagger\Tag\CreateComment;

class CreateCommentTest extends TestCase
{
    public function testCreateSqlCommentReturnsProperArray(): void
    {
        $file       = __FILE__;
        $lineNumber = __LINE__;
        $function   = __METHOD__;

        $result = CreateComment::sql($file, $lineNumber, $function);

        self::assertSame('File: ' . $file, $result[0]);
        self::assertSame('Line: ' . $lineNumber, $result[1]);
        self::assertSame('Function: ' . $function, $result[2]);
    }
}
