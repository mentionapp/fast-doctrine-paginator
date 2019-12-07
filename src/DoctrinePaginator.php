<?php

namespace Mention\FastDoctrinePaginator;

use Assert\Assertion;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Mention\FastDoctrinePaginator\Internal\CursorEncoder;
use Mention\FastDoctrinePaginator\Internal\EnsureNoFetchJoinWalker;
use Mention\FastDoctrinePaginator\Internal\EnsureOrderByWalker;
use Mention\Paginator\PaginatorInterface;
use Mention\Paginator\PaginatorItem;
use Mention\Paginator\PaginatorPage;
use Mention\Paginator\PaginatorPageInterface;

/**
 * DoctrinePaginator paginates a Query.
 *
 * ## Using a DoctrinePaginator instance
 *
 * See PaginatorInterface
 *
 * When paginating large queries, it's recommended to clear entity
 * managers between pages.
 *
 * ## General principle
 *
 * The query is used to fetch the results for one page of results, and will be
 * executed multiple times when fetching multiple pages. Pagination stops when
 * the query returns no results.
 *
 * The number of elements per page is defined by calling setMaxResults() on the
 * query itself.
 *
 * Pagination is value-based, rather than limit/offset-based: Instead of asking
 * for the Nth rows in a query, we ask for the rows that are upper than
 * the higher one of the previous page. When comparing rows, we only take into
 * account one or a few columns, that we call the discriminators. We use the
 * value of the discriminator columns of the last row of one page to create an
 * internal cursor that can be used to fetch the next page.
 *
 * For this to work effectively and flawlessly, the following
 * conditions must be true:
 *
 * - The column(s) used for discrimination must be unique (if it's not the case,
 *   a combination of multiple discriminators must be used)
 * - The query must be ordered by the discrimination columns
 * - The query must have a WHERE clause that selects only rows whose
 *   discriminators are higher than the higher discriminators of the previous
 *   page.
 *
 * ## About the query
 *
 * The query must be a Doctrine Query object. It must have a defined number
 * of max results (setMaxResults()), because this defines the number of
 * items per page.
 *
 * It must be ordered by the discriminator columns.
 *
 * It must have a WHERE clause that selects only the rows whose
 * discriminators are higher than the ones of the previous page. The
 * paginator calls setParameter() on the query to set these values.
 *
 * ## Examples
 *
 * Examples with a Users table:
 *
 * +-------+--------------+------+-----+---------+----------------+
 * | Field | Type         | Null | Key | Default | Extra          |
 * +-------+--------------+------+-----+---------+----------------+
 * | id    | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | name  | varchar(255) | NO   |     | NULL    |                |
 * +-------+--------------+------+-----+---------+----------------+
 *
 * +----+---------+
 * | id | name    |
 * +----+---------+
 * |  1 | Jackson |
 * |  2 | Sophia  |
 * |  3 | Aiden   |
 * |  4 | Olivia  |
 * |  5 | Lucas   |
 * |  6 | Ava     |
 * +----+---------+
 *
 * If we sort by id, we can use id as discriminator, because it's unique.
 *
 * Note the ORDER clause, that orders by our discriminator.
 *
 * Note the WHERE clause, that discriminates by our discriminator. We use the
 * query parameter :idCursor. The value of this parameter is automatically
 * set by the paginator. By default, it's set to `0` when requesting the
 * first page, and then it's automatically updated to the value found in the
 * last row of the latest fetched page.
 *
 * $query = $entityManager->createQuery('
 *     SELECT   u.id, u.name
 *     FROM     Users u
 *     WHERE    u.id > :idCursor
 *     ORDER    BY u.id ASC
 * ');
 *
 * // Max results per page
 * $query->setMaxResults(3);
 *
 * $paginator = DoctrinePaginatorBuilder::new()
 *     ->setQuery($query)
 *     ->setDiscriminators([
 *         new PageDiscriminatorInterface('idCursor', 'getId'),
 *     ])
 *     ->build();
 *
 * foreach ($paginator as $page) {
 *     foreach ($page() as $result) {
 *         // ...
 *     }
 *     $em->clear();
 * }
 *
 * The first page will return this:
 *
 * +----+---------+
 * | id | name    |
 * +----+---------+
 * |  1 | Jackson |
 * |  2 | Sophia  |
 * |  3 | Aiden   |
 * +----+---------+
 *
 * The paginator retains id=3 as cursor internally. Before requesting the
 * next page, the paginator calls setParameter('idCursor', 3) on the query.
 * Naturally, the second page returns this:
 *
 * +----+--------+
 * | id | name   |
 * +----+--------+
 * |  4 | Olivia |
 * |  5 | Lucas  |
 * |  6 | Ava    |
 * +----+--------+
 *
 * Sorting by name:
 *
 * If we sort by name, we can not use it directly as discriminator, because
 * it's not unique. If we sort by name and id, we can use name and id as
 * discriminators, because they are unique together.
 *
 * Notice how we use u.id as a fallback when the name equals to the current
 * name cursor.
 *
 * $query = $entityManager->createQuery('
 *     SELECT   u.id, u.name
 *     FROM     Users u
 *     WHERE    u.name > :nameCursor
 *     OR       (u.name = :nameCursor AND u.id > :idCursor)
 *     ORDER    BY u.name ASC, u.id ASC
 * ');
 *
 * $paginator = (new DoctrinePaginatorBuilder())
 *     ->setQuery($query)
 *     ->setDiscriminators([
 *         new PageDiscriminatorInterface('nameCursor', 'getName'),
 *         new PageDiscriminatorInterface('idCursor', 'getId'),
 *     ])
 *     ->build();
 *
 * ## Resuming pagination
 *
 * We can resume pagination on a new DoctrinePaginator instance by setting
 * the cursor explicitly. This is useful when paginating through multiple
 * requests, for example.
 *
 * The end cursor of a page can be retrieved by calling getCursor() on a
 * PaginatorItem object. Using this cursor will fetch the next page.
 *
 * A paginator that will resume at this position can be built by calling
 * setCursor() on a DoctrinePaginatorBuilder.
 *
 * ## Discriminator-based pagination versus LIMIT/OFFSET pagination
 *
 * Whereas [LIMIT, OFFSET] forces the RDBMS to find and skip every row before
 * the requested page (every row before the given offset), discriminator-based
 * pagination allows it to start directly at the first row of the requested
 * page.
 *
 * ## Batch jobs
 *
 * This paginator is particularly suitable for batching, because it give the
 * user an opportunity to act before and after every page.
 *
 * For example, the entity manager can be safely cleared between two pages:
 *
 * foreach ($paginator as $page) {
 *     foreach ($page() as $result) {
 *         // ...
 *     }
 *     $em->clear();
 * }
 *
 * ## GraphQL/Relay
 *
 * This paginator is particularly suitable for GraphQL/Relay pagination, since
 * it provides cursors for the pages and items:
 *
 * foreach ($paginator as $page) {
 *     // $page is a PaginatorPageInterface
 *     // Get the cursor for the first item of the page:
 *     // $startCursor = $page->firstCursor();
 *     // Get the cursor for the last item of the page:
 *     // $startCursor = $page->endCursor();
 *     foreach ($page->items() as $item) {
 *         // Get the cursor for this item:
 *         // $item->getCursor();
 *     }
 * }
 */
final class DoctrinePaginator implements PaginatorInterface
{
    /** @var CursorEncoder */
    private $cursorEncoder;

    /** @var \Iterator<int, PaginatorPageInterface> */
    private $generator;

    /**
     * @param PageDiscriminatorInterface[] $discriminators
     * @param int                          $hydrationMode  one of:
     *                                                     - Query::HYDRATE_OBJECT
     *                                                     - Query::HYDRATE_ARRAY
     *                                                     - Query::HYDRATE_SCALAR
     * @param string|null                  $cursor         A cursor obtained from PaginatorPageInterface::getCursor()
     */
    public function __construct(
        AbstractQuery $query,
        array $discriminators,
        int $hydrationMode = Query::HYDRATE_OBJECT,
        ?string $cursor = null
    ) {
        Assertion::minCount(
            $discriminators,
            1,
            'DoctrinePaginator must have at least one discriminator, none given'
        );

        if ($query instanceof Query) {
            Assertion::notNull(
                $query->getMaxResults(),
                'Query must have a defined max number of results (Query::setMaxResults()). This defines the number of elements per page.'
            );
        }

        $discriminators = $this->reindexDiscriminators($discriminators);

        $attrs = [];
        foreach ($discriminators as $key => $discriminator) {
            $attrs[$key] = $discriminator->getValueResolver($hydrationMode);
        }

        $this->addSanityTreeWalkers($query);

        $this->cursorEncoder = new CursorEncoder(
            $query->getSQL(),
            array_keys($discriminators)
        );

        $from = null === $cursor
            ? array_map(function (PageDiscriminatorInterface $discriminator) {
                return $discriminator->getDefaultValue();
            }, $discriminators)
            : $this->cursorEncoder->decode($cursor);

        $this->generator = $this->createGenerator(
            $query,
            $hydrationMode,
            $attrs,
            $from
        );
    }

    /**
     * Implementation of IteratorAggregate::getIterator().
     *
     * Foreach calls this method when iterating over a DoctrinePaginator.
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->generator;
    }

    /**
     * @param array<string,callable(mixed):(scalar|\DateTimeInterface)> $attrs
     * @param mixed[]                                                   $from
     *
     * @phpstan-return \Generator<int, PaginatorPageInterface, mixed, mixed>
     */
    private function createGenerator(
        AbstractQuery $query,
        int $hydrationMode,
        array $attrs,
        array $from
    ) {
        while (true) {
            foreach ($from as $param => $value) {
                $query->setParameter($param, $value);
            }

            $results = $query->execute(null, $hydrationMode);
            if (count($results) === 0) {
                return;
            }

            $items = [];
            foreach ($results as $result) {
                $from = $this->getFrom($attrs, $result);
                $items[] = new DoctrinePaginatorItem(
                    $this->cursorEncoder,
                    $from,
                    $result
                );
            }

            yield new PaginatorPage($items);
        }
    }

    /**
     * @param PageDiscriminatorInterface[] $discriminators
     *
     * @phpstan-return array<string,PageDiscriminatorInterface>
     */
    private function reindexDiscriminators(array $discriminators): array
    {
        $ret = [];
        foreach ($discriminators as $discriminator) {
            $ret[$discriminator->getQueryParamName()] = $discriminator;
        }

        return $ret;
    }

    private function addSanityTreeWalkers(AbstractQuery $query): void
    {
        $treeWalkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if (!is_array($treeWalkers)) {
            $treeWalkers = [];
        }

        $treeWalkers[] = EnsureNoFetchJoinWalker::class;
        $treeWalkers[] = EnsureOrderByWalker::class;

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $treeWalkers);
    }

    /**
     * @param array<string,callable(mixed):mixed> $attrs
     * @param mixed[]|object                      $result
     *
     * @return array<string,scalar>
     */
    private function getFrom(array $attrs, $result): array
    {
        $from = [];

        foreach ($attrs as $key => $attr) {
            $value = $attr($result);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            Assertion::scalar(
                $value,
                sprintf(
                    'Value for discriminator "%s" must be a scalar, got a "%%s"',
                    $key
                )
            );

            $from[$key] = $value;
        }

        return $from;
    }
}
