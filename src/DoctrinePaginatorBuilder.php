<?php

namespace Mention\FastDoctrinePaginator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Mention\FastDoctrinePaginator\Internal\TypedQuery;

/**
 * @template ItemT
 *
 * @see DoctrinePaginator
 */
final class DoctrinePaginatorBuilder
{
    /** @var QueryInterface<ItemT> */
    private QueryInterface $query;

    /** @var array<int,PageDiscriminator> */
    private array $discriminators;

    private ?string $cursor;

    /**
     * @param QueryInterface<ItemT>        $query
     * @param array<int,PageDiscriminator> $discriminators
     * @param string|null                  $cursor
     */
    private function __construct(QueryInterface $query, array $discriminators, ?string $cursor)
    {
        $this->query = $query;
        $this->discriminators = $discriminators;
        $this->cursor = $cursor;
    }

    /**
     * @template QueryItemT
     *
     * @param AbstractQuery<QueryItemT> $query
     *
     * @return self<QueryItemT>
     */
    public static function fromQuery(AbstractQuery $query): self
    {
        return new self(new TypedQuery($query), [], null);
    }

    /**
     * Set the discriminators to use for pagination.
     *
     * @see DoctrinePaginator
     *
     * @param PageDiscriminator[] $discriminators
     *
     * @return self<ItemT>
     */
    public function setDiscriminators(array $discriminators): self
    {
        $this->discriminators = $discriminators;

        return $this;
    }

    /**
     * Set the hydration mode.
     *
     * This is Query::HYDRATE_OBJECT by default.
     *
     * @param Query::HYDRATE_* $hydrationMode
     *
     * @return self<mixed>
     */
    public function withHydrationMode($hydrationMode): self
    {
        return new self(
            $this->query->withHydrationMode($hydrationMode),
            $this->discriminators,
            $this->cursor,
        );
    }

    /**
     * Define where the pagination is resumed.
     *
     * This method can be called to resume pagination as some specific point.
     *
     * Use Mention\CoreBundle\Paginator\PaginatorPageInterface::endCursor()
     * to get a valid cursor.
     *
     * @return self<ItemT>
     */
    public function setCursor(?string $cursor): self
    {
        $this->cursor = $cursor;

        return $this;
    }

    /**
     * @return DoctrinePaginator<ItemT>
     */
    public function build(): DoctrinePaginator
    {
        return new DoctrinePaginator(
            $this->query,
            $this->discriminators,
            $this->cursor,
        );
    }
}
