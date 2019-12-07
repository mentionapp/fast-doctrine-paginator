<?php

namespace Mention\FastDoctrinePaginator;

/**
 * Represents one column used for discrimination.
 *
 * @see DoctrinePaginator
 * @see PageDiscriminator
 */
interface PageDiscriminatorInterface
{
    public function getQueryParamName(): string;

    /**
     * @return string|callable(mixed):(scalar|\DateTimeInterface)
     */
    public function getAttribute();

    /**
     * @return scalar|\DateTimeInterface
     */
    public function getDefaultValue();

    /**
     * @return callable(mixed):(scalar|\DateTimeInterface)
     */
    public function getValueResolver(int $hydrationMode): callable;
}
