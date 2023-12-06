<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\PartialObjectExpression;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Checks that a query has no fetch joins.
 *
 * @internal
 */
final class EnsureNoFetchJoinWalker extends SqlWalker
{
    public const HINT_DISTINCT = self::class.'.distinct';

    /**
     * @var array<array-key,array{
     *     class: ClassMetadata<object>,
     *     dqlAlias: string,
     *     resultAlias: ?string,
     * }>
     */
    private $selectedClasses;

    public function walkSelectClause($selectClause)
    {
        if ($selectClause->isDistinct) {
            $this->getQuery()->setHint(self::HINT_DISTINCT, true);
        }

        return parent::walkSelectClause($selectClause);
    }

    public function walkSelectExpression($selectExpression)
    {
        $expr = $selectExpression->expression;

        switch (true) {
            case $expr instanceof AST\PathExpression:
            case $expr instanceof AST\AggregateExpression:
            case $expr instanceof AST\Functions\FunctionNode:
            case $expr instanceof AST\SimpleArithmeticExpression:
            case $expr instanceof AST\ArithmeticTerm:
            case $expr instanceof AST\ArithmeticFactor:
            case $expr instanceof AST\ParenthesisExpression:
            case $expr instanceof AST\Literal:
            case $expr instanceof AST\NullIfExpression:
            case $expr instanceof AST\CoalesceExpression:
            case $expr instanceof AST\GeneralCaseExpression:
            case $expr instanceof AST\SimpleCaseExpression:
            case $expr instanceof AST\Subselect:
            case $expr instanceof AST\NewObjectExpression:
                break;
            default:
                // IdentificationVariable or PartialObjectExpression
                if ($expr instanceof AST\PartialObjectExpression) {
                    $dqlAlias = $expr->identificationVariable;
                } else {
                    $dqlAlias = $expr;
                }

                assert(is_string($dqlAlias));

                $queryComp = $this->getQueryComponent($dqlAlias);
                if (!isset($queryComp['metadata'])) {
                    throw new \Exception(
                        sprintf('No metadata found for DQL alias: %s', $dqlAlias),
                    );
                }

                $class = $queryComp['metadata'];
                $resultAlias = $selectExpression->fieldIdentificationVariable;

                if (!isset($this->selectedClasses[$dqlAlias])) {
                    $this->selectedClasses[$dqlAlias] = [
                        'class' => $class,
                        'dqlAlias' => $dqlAlias,
                        'resultAlias' => $resultAlias,
                    ];
                }
        }

        return parent::walkSelectExpression($selectExpression);
    }

    public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = Join::JOIN_TYPE_INNER, $condExpr = null)
    {
        $joinedDqlAlias = $joinAssociationDeclaration->aliasIdentificationVariable;

        $queryComp = $this->getQueryComponent($joinedDqlAlias);

        if (!isset($queryComp['relation'])) {
            throw new \Exception(
                sprintf('No relation found for join association: %s', $joinedDqlAlias),
            );
        }

        $relation = $queryComp['relation'];
        assert(class_exists($relation['targetEntity']));
        $targetClass = $this->getEntityManager()->getClassMetadata($relation['targetEntity']);

        // Ensure we got the owning side, since it has all mapping info
        $assoc = (!$relation['isOwningSide']) ? $targetClass->associationMappings[$relation['mappedBy']] : $relation;

        if ($this->getQuery()->getHint(self::HINT_DISTINCT) !== true && isset($this->selectedClasses[$joinedDqlAlias])) {
            if ($relation['type'] == ClassMetadata::ONE_TO_MANY || $relation['type'] == ClassMetadata::MANY_TO_MANY) {
                throw new QueryException(sprintf(
                    'Paginate with a OneToMany or ManyToMany join in class %s '.
                    'using association %s not allowed. '.
                    'Trying to fetch-join a collection in a query that has a '.
                    'MaxResults constraint will not work as one would expect, '.
                    'as collections may be cut in half by the MaxResults '.
                    'constraint. Consider removing this JOIN.',
                    $relation['sourceEntity'],
                    $relation['fieldName'],
                ));
            }
        }

        return parent::walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType, $condExpr);
    }
}
