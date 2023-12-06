<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Mention\Kebab\Pcre\Exception\PcreException;
use Mention\Kebab\Pcre\PcreUtils;

/**
 * Checks that a query has a LIMIT clause.
 *
 * @internal
 */
final class EnsureMaxResultWalker extends TreeWalkerAdapter
{
    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(SelectStatement $AST): string
    {
        $query = $this->_getQuery();

        $hasLimitClause = match (true) {
            $query instanceof Query => $query->getMaxResults() !== null,
            // All others queries can only be an AbstractQuery (typeof _getQuery())
            default => $this->hasLimitClause($query->getSQL()),
        };

        if (!$hasLimitClause) {
            throw new \InvalidArgumentException('The pagination query must have a LIMIT clause.');
        }

        return $AST;
    }

    /**
     * Check if the LIMIT clause is present in a SQL query.
     *
     * @param string[]|string $query
     *
     * @throws PcreException
     */
    private function hasLimitClause(string|array $query): bool
    {
        assert(is_string($query));

        return PcreUtils::match(
            '#\s+LIMIT\s+\d+/i#',
            $query,
        );
    }
}
