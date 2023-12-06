<?php

namespace Mention\FastDoctrinePaginator;

use Doctrine\ORM\Query;

/**
 * A wrapper for AbstractQuery whose item type is static.
 *
 * @template ItemT
 */
interface QueryInterface
{
    /**
     * @param Query::HYDRATE_* $hydrationMode
     *
     * @return QueryInterface<ItemT>
     */
    public function withHydrationMode(int $hydrationMode);

    /**
     * @return Query::HYDRATE_*
     */
    public function getHydrationMode(): int;

    /**
     * @param mixed $value
     */
    public function setParameter(string $name, $value): void;

    /**
     * @return array<ItemT>
     */
    public function execute(): array;

    /**
     * @return string[]|string
     */
    public function getSQL(): array|string;

    /**
     * @return mixed
     */
    public function getHint(string $name);

    /**
     * @param mixed $hint
     */
    public function setHint(string $name, $hint): void;
}
