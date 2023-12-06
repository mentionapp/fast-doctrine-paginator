<?php

namespace Mention\FastDoctrinePaginator;

/**
 * Represents one column used for discrimination.
 *
 * @see DoctrinePaginator
 */
final class PageDiscriminator
{
    /**
     * @var string
     */
    public $queryParamName;

    /**
     * @var string|callable(mixed):(scalar|\DateTimeInterface)
     */
    public $attribute;

    /**
     * @var scalar|\DateTimeInterface
     */
    public $defaultValue;

    /**
     * PageDiscriminator constructor.
     *
     * Query param name:
     *
     * When using a column 'id' as discriminator, and a query like
     * 'WHERE u.id > :idCursor', use 'idCursor' as $queryParamName.
     *
     * Attribute:
     *
     * This defines how the paginator will retrieves the value of the
     * discriminator column in each result.
     *
     * If $attribute is a string, it's assumed to be the name of a method
     * on the result objects (in object hydration mode), or the name of an
     * array key on the result arrays (in array hydration mode).
     *
     * If $attribute is a callable, it's called directly, with one result
     * item as argument.
     *
     * When using a column 'id' as discriminator, if the items are objects
     * with a 'getId' method, the following examples are valid values for
     * $attribute:
     *
     * - 'getId'
     * - function ($item): string { return $item->getId(); }
     *
     * Default value:
     *
     * The default value is used when fetching the first page. For most numeric
     * columns, the value 0 is valid (this is the default).
     *
     * @param string                                             $queryParamName See setQueryParamName()
     * @param string|callable(mixed):(scalar|\DateTimeInterface) $attribute      See setAttribute()
     * @param scalar|\DateTimeInterface                          $defaultValue   See setDefaultValue()
     */
    public function __construct(string $queryParamName, $attribute, $defaultValue = 0)
    {
        $this->setQueryParamName($queryParamName);
        $this->setAttribute($attribute);
        $this->setDefaultValue($defaultValue);
    }

    /**
     * Sets the name of the query-parameter for this column.
     *
     * When using a column 'id' as discriminator, and a query like
     * 'WHERE u.id > :idCursor', the query-parameter name is 'idCursor'.
     */
    public function setQueryParamName(string $queryParamName): self
    {
        $this->queryParamName = $queryParamName;

        return $this;
    }

    public function getQueryParamName(): string
    {
        return $this->queryParamName;
    }

    /**
     * Sets the attribute related to the discriminator column.
     *
     * This defines how the paginator will retrieves the value of the
     * discriminator column in each result.
     *
     * If $attribute is a string, it's assumed to be the name of a method
     * on the result objects (in object hydration mode), or the name of an
     * array key on the result arrays (in array hydration mode).
     *
     * If $attribute is a callable, it's called directly, with one result
     * item as argument.
     *
     * When using a column 'id' as discriminator, if the items are objects
     * with a 'getId' method, the following examples are valid values for
     * $attribute:
     *
     * - 'getId'
     * - function ($item): string { assert($item instanceof Something); return $item->getId(); }
     *
     * The default value is used when fetching the first page. For most numeric
     * columns, the value 0 is valid (this is the default).
     *
     * @param string|callable(mixed):(scalar|\DateTimeInterface) $attribute
     */
    public function setAttribute($attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @return string|callable(mixed):(scalar|\DateTimeInterface)
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Sets the default value of a discriminator.
     *
     * This value is used when fetching the first page. For most numeric
     * columns, the value '0' is valid (this is the default).
     *
     * @param scalar|\DateTimeInterface $value
     */
    public function setDefaultValue($value): self
    {
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * @return scalar|\DateTimeInterface
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
