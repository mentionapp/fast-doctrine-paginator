<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Mention\FastDoctrinePaginator\QueryInterface;

/**
 * A QueryInterface whose item type is known statically.
 *
 * @template ItemT
 *
 * @implements QueryInterface<ItemT>
 *
 * @internal
 */
final class TypedQuery implements QueryInterface
{
    /** @var AbstractQuery<ItemT> */
    private AbstractQuery $query;

    /** @param AbstractQuery<ItemT> $query */
    public function __construct(AbstractQuery $query)
    {
        $this->query = $query;
    }

    public function withHydrationMode(int $hydrationMode)
    {
        return new MixedQuery($this->query, $hydrationMode);
    }

    public function getHydrationMode(): int
    {
        return Query::HYDRATE_OBJECT;
    }

    public function setParameter(string $name, $value): void
    {
        $this->query->setParameter($name, $value);
    }

    public function execute(): array
    {
        return $this->query->execute();
    }

    public function getHint(string $name)
    {
        return $this->query->getHint($name);
    }

    public function setHint(string $name, $hint): void
    {
        $this->query->setHint($name, $hint);
    }

    /**
     * @return string[]|string
     */
    public function getSQL(): array|string
    {
        return $this->query->getSQL();
    }
}
