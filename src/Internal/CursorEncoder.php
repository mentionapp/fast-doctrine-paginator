<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Assert\Assertion;
use Mention\FastDoctrinePaginator\InvalidCursorException;
use Mention\Kebab\Json\Exception\JsonUtilsException;
use Mention\Kebab\Json\JsonUtils;
use Mention\Kebab\Pcre\PcreUtils;

/** @internal */
final class CursorEncoder
{
    /** @var string */
    private $sqlChecksum;

    /** @var string */
    private $attrChecksum;

    /** @var string[] */
    private $attrKeys;

    /**
     * @param string[] $attrKeys
     */
    public function __construct(
        string $sql,
        array $attrKeys
    ) {
        $this->sqlChecksum = $this->sqlChecksum($sql);
        $this->attrChecksum = $this->attrChecksum($attrKeys);
        $this->attrKeys = $attrKeys;
    }

    /**
     * Unserializes the cursor.
     *
     * Throws a InvalidCursorException if the paginator has
     * changed (e.g. the SQL query is not the same).
     *
     * @return scalar[]
     */
    public function decode(string $encodedCursor): array
    {
        $cursor = base64_decode($encodedCursor, true);

        if (!is_string($cursor) || strlen($cursor) < 64) {
            throw new InvalidCursorException('Invalid cursor: Invalid format');
        }

        $sqlChecksum = substr($cursor, 0, 32);
        $attrChecksum = substr($cursor, 32, 32);

        try {
            $attrs = JsonUtils::decodeArray(substr($cursor, 64));
        } catch (JsonUtilsException $e) {
            throw new InvalidCursorException('Invalid cursor: Invalid format', 0, $e);
        }

        if (!hash_equals($this->sqlChecksum, $sqlChecksum)) {
            throw new InvalidCursorException('Invalid cursor: This cursor was not generated for this query (or it has changed)');
        }

        if (!hash_equals($this->attrChecksum, $attrChecksum)) {
            throw new InvalidCursorException('Invalid cursor: This cursor was not generated for this set of query attrs (or it has changed)');
        }

        if (count($attrs) !== count($this->attrKeys)) {
            throw new InvalidCursorException('Invalid cursor: Invalid number of query attributes');
        }

        $from = array_combine($this->attrKeys, $attrs);
        if ($from === false) {
            throw new InvalidCursorException('Invalid cursor: Unexpected error');
        }

        return $from;
    }

    /**
     * @param scalar[] $from
     */
    public function encode(array $from): string
    {
        Assertion::count($from, count($this->attrKeys));

        return base64_encode(
            $this->sqlChecksum
            .$this->attrChecksum
            .JsonUtils::encode(array_values($from))
        );
    }

    private function sqlChecksum(string $sql): string
    {
        $sql = PcreUtils::replaceArray(['#\s*LIMIT\s*\d+\s*;?$#' => ''], $sql);

        return hash('sha256', $sql, true);
    }

    /**
     * @param string[] $attrKeys
     */
    private function attrChecksum(array $attrKeys): string
    {
        return hash('sha256', implode('/', $attrKeys), true);
    }
}
