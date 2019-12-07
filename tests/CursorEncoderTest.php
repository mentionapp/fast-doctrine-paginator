<?php

namespace Mention\FastDoctrinePaginator\Tests;

use Mention\FastDoctrinePaginator\Internal\CursorEncoder;
use Mention\FastDoctrinePaginator\InvalidCursorException;
use PHPUnit\Framework\TestCase;

class CursorEncoderTest extends TestCase
{
    public function testEncodeDecodeValidCursor(): void
    {
        $encoder = new CursorEncoder(
            'SELECT 1',
            ['id', 'createdAt']
        );

        $cursor = $encoder->encode([123, '2018-01-29']);
        $values = $encoder->decode($cursor);

        self::assertSame([
            'id' => 123,
            'createdAt' => '2018-01-29',
        ], $values);
    }

    public function testEncodeDecodeValidCursorWithLimit(): void
    {
        $encoder = new CursorEncoder(
            'SELECT 1 LIMIT 10',
            ['id', 'createdAt']
        );

        $cursor = $encoder->encode([123, '2018-01-29']);

        $encoder = new CursorEncoder(
            'SELECT 1 LIMIT 200',
            ['id', 'createdAt']
        );

        $values = $encoder->decode($cursor);

        self::assertSame([
            'id' => 123,
            'createdAt' => '2018-01-29',
        ], $values);
    }

    public function testDecodeInvalidCursorInvalidPayload(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Invalid cursor: Invalid format');

        $encoder = new CursorEncoder(
            'SELECT 1',
            ['id', 'createdAt']
        );

        $values = $encoder->decode(base64_encode(''));
    }

    public function testDecodeInvalidCursorChangedQuery(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Invalid cursor: This cursor was not generated for this query (or it has changed)');

        $encoder = new CursorEncoder(
            'SELECT 1',
            ['id', 'createdAt']
        );

        $cursor = $encoder->encode([123, '2018-01-29']);

        $encoder = new CursorEncoder(
            'SELECT 2',
            ['id', 'createdAt']
        );

        $values = $encoder->decode($cursor);
    }

    public function testDecodeInvalidCursorChangedAttrs(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Invalid cursor: This cursor was not generated for this set of query attrs (or it has changed)');

        $encoder = new CursorEncoder(
            'SELECT 1',
            ['id', 'createdAt']
        );

        $cursor = $encoder->encode([123, '2018-01-29']);

        $encoder = new CursorEncoder(
            'SELECT 1',
            ['id', 'updatedAt']
        );

        $values = $encoder->decode($cursor);
    }
}
