<?php

namespace Mention\FastDoctrinePaginator\Internal;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Mention\FastDoctrinePaginator\QueryInterface;

/**
 * A QueryInterface whose item type is mixed because it can not be determined statically.
 *
 * @template ItemT
 *
 * @implements QueryInterface<mixed>
 *
 * @internal
 */
final class MixedQuery implements QueryInterface
{
    /** @var AbstractQuery<ItemT> */
    private AbstractQuery $query;

    /** @var Query::HYDRATE_* */
    private int $hydrationMode;

    /**
     * @param AbstractQuery<ItemT> $query
     * @param Query::HYDRATE_*     $hydrationMode
     */
    public function __construct(AbstractQuery $query, int $hydrationMode)
    {
        $this->query = $query;
        $this->hydrationMode = $hydrationMode;
    }

    public function withHydrationMode(int $hydrationMode)
    {
        return new self($this->query, $hydrationMode);
    }

    public function getHydrationMode(): int
    {
        return $this->hydrationMode;
    }

    public function setParameter(string $name, $value): void
    {
        $this->query->setParameter($name, $value);
    }

    public function execute(): array
    {
        $result = $this->query->execute(null, $this->hydrationMode);
        assert(is_array($result));

        return $result;
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
