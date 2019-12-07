<?php

namespace Mention\FastDoctrinePaginator;

use Assert\Assertion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

/**
 * @see DoctrinePaginator
 */
final class DoctrinePaginatorBuilder
{
    /** @var AbstractQuery|null */
    private $query;

    /** @var PageDiscriminator[] */
    private $discriminators = [];

    /** @var int */
    private $hydrationMode = Query::HYDRATE_OBJECT;

    /** @var string|null */
    private $cursor;

    public static function new(): self
    {
        return new self();
    }

    public function setQuery(AbstractQuery $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the discriminators to use for pagination.
     *
     * @see DoctrinePaginator
     *
     * @param PageDiscriminator[] $discriminators
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
     * @param mixed $hydrationMode one of:
     *                             - Query::HYDRATE_OBJECT
     *                             - Query::HYDRATE_ARRAY
     *                             - Query::HYDRATE_SCALAR
     */
    public function setHydrationMode($hydrationMode): self
    {
        $this->hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Define where the pagination is resumed.
     *
     * This method can be called to resume pagination as some specific point.
     *
     * Use Mention\CoreBundle\Paginator\PaginatorPageInterface::endCursor()
     * to get a valid cursor.
     */
    public function setCursor(?string $cursor): self
    {
        $this->cursor = $cursor;

        return $this;
    }

    public function build(): DoctrinePaginator
    {
        Assertion::notNull($this->query);

        return new DoctrinePaginator(
            $this->query,
            $this->discriminators,
            $this->hydrationMode,
            $this->cursor
        );
    }
}
