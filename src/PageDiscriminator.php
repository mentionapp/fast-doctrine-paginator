<?php

namespace Mention\FastDoctrinePaginator;

use Doctrine\ORM\Query;

/**
 * Represents one column used for discrimination.
 *
 * @see DoctrinePaginator
 */
final class PageDiscriminator implements PageDiscriminatorInterface
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
        $this->queryParamName = $queryParamName;
        $this->attribute = $attribute;
        $this->defaultValue = $defaultValue;
    }

    public function getQueryParamName(): string
    {
        return $this->queryParamName;
    }

    /**
     * @return string|callable(mixed):(scalar|\DateTimeInterface)
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return scalar|\DateTimeInterface
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return callable(mixed):(scalar|\DateTimeInterface)
     */
    public function getValueResolver(int $hydrationMode): callable
    {
        $attr = $this->getAttribute();

        if (is_string($attr)) {
            if ($hydrationMode === Query::HYDRATE_OBJECT) {
                return static function (object $entity) use ($attr) {
                    return $entity->{$attr}();
                };
            }

            if ($hydrationMode === Query::HYDRATE_ARRAY ||
                $hydrationMode === Query::HYDRATE_SCALAR
            ) {
                return static function (array $row) use ($attr) {
                    return $row[$attr];
                };
            }

            throw new \RuntimeException();
        }

        return $attr;
    }
}
