<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * Checks that a query has an ORDER BY clause.
 *
 * @internal
 */
final class EnsureOrderByWalker extends TreeWalkerAdapter
{
    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(SelectStatement $AST): string
    {
        if (null === $AST->orderByClause) {
            throw new \InvalidArgumentException('The pagination query must have an ORDER BY clause.');
        }

        return $AST;
    }
}
