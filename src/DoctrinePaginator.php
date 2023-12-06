<?php

namespace Mention\FastDoctrinePaginator;

use Assert\Assertion;
use Doctrine\ORM\Query;
use Mention\FastDoctrinePaginator\Internal\CursorEncoder;
use Mention\FastDoctrinePaginator\Internal\EnsureMaxResultWalker;
use Mention\FastDoctrinePaginator\Internal\EnsureNoFetchJoinWalker;
use Mention\FastDoctrinePaginator\Internal\EnsureOrderByWalker;
use Mention\Paginator\GeneratorPaginatorTrait;
use Mention\Paginator\PaginatorInterface;
use Mention\Paginator\PaginatorItem;
use Mention\Paginator\PaginatorPage;

/**
 * DoctrinePaginator paginates a Query.
 *
 * ## Using a DoctrinePaginator instance
 *
 * See PaginatorInterface
 *
 * ## Memory usage
 *
 * When iterating over many pages of results, it is important to clear entity
 * managers between pages:
 *
 * foreach ($paginator as $page) {
 *     // $page is a PaginatorPageInterface
 *     foreach ($page->items() as $item) {
 *         // ...
 *     }
 *     $em->clear(); // or other higher level methods to achieve this
 * }
 *
 * See rationale later in this doc block.
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
 * - The query must be ordered by all the discrimination columns
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
 * Note the WHERE clause, that filters by our discriminator. We use the
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
 * $paginator = DoctrinePaginatorBuilder::fromQuery($query)
 *     ->setDiscriminators([
 *         new PageDiscriminator('idCursor', 'getId'),
 *     ])
 *     ->build();
 *
 * foreach ($paginator as $page) {
 *     foreach ($page() as $result) {
 *         // ...
 *     }
 *     $entityManager->clear();
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
 *         new PageDiscriminator('nameCursor', 'getName'),
 *         new PageDiscriminator('idCursor', 'getId'),
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
 * This paginator is particularly suitable for batching, because it gives the
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
 *      // $page is a PaginatorPageInterface
 *      // Get the cursor for the first item of the page:
 *      // $startCursor = $page->firstCursor();
 *      // Get the cursor for the last item of the page:
 *      // $startCursor = $page->endCursor();
 *      foreach ($page->items() as $item) {
 *          // Get the cursor for this item:
 *          // $item->getCursor();
 *      }
 *  }
 *
 * ## About memory usage
 *
 * DoctrinePaginator does not clear entity managers automatically between pages
 * as this would implicitly detach all entities (including entities fetched by
 * other means), which can lead to issues such as state corruptions (in the
 * worst case) or Doctrine errors that are difficult to debug. Forcing users to
 * do it explicitly ensures that they are aware of it.
 *
 * In the current design it is possible that users forget about clearing
 * entity managers, which can lead to OOMs. This is a less critical issue as
 * this does not lead to difficult Doctrine exceptions or state corruptions due
 * to unsuspectingly handling detached entities.
 *
 * @template ItemT
 *
 * @implements PaginatorInterface<ItemT>
 */
final class DoctrinePaginator implements PaginatorInterface
{
    /**
     * implementation of current()/key()/next()/rewind()/valid().
     *
     * @use GeneratorPaginatorTrait<ItemT>
     */
    use GeneratorPaginatorTrait;

    /** @var CursorEncoder */
    private $cursorEncoder;

    /** @var \Generator<int,PaginatorPage<ItemT>> */
    private \Generator $generator;

    /**
     * @param QueryInterface<ItemT>        $query
     * @param array<int,PageDiscriminator> $discriminators
     * @param string|null                  $cursor         A cursor obtained from PaginatorPageInterface::getCursor()
     */
    public function __construct(
        QueryInterface $query,
        array $discriminators,
        ?string $cursor = null,
    ) {
        Assertion::minCount(
            $discriminators,
            1,
            'DoctrinePaginator must have at least one discriminator, none given',
        );

        $discriminators = $this->reindexDiscriminators($discriminators);

        $hydrationMode = $query->getHydrationMode();
        $attrs = array_map(
            function (PageDiscriminator $discr) use ($hydrationMode) {
                $attr = $discr->getAttribute();

                if (is_string($attr)) {
                    if ($hydrationMode === Query::HYDRATE_OBJECT) {
                        return function ($entity) use ($attr) {
                            $fetch = [$entity, $attr];
                            assert(is_callable($fetch));

                            return $fetch();
                        };
                    }
                    if ($hydrationMode === Query::HYDRATE_ARRAY ||
                        $hydrationMode === Query::HYDRATE_SCALAR
                    ) {
                        return function ($row) use ($attr) {
                            assert(is_array($row) && isset($row[$attr]));

                            return $row[$attr];
                        };
                    }
                }
                if (is_callable($attr)) {
                    return $attr;
                }

                // PageDiscriminator wouldn't allow this to happen
                throw new \Exception();
            },
            $discriminators,
        );

        // Add a tree walker, for sanity checks
        $treeWalkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);
        if (is_array($treeWalkers)) {
            $treeWalkers[] = EnsureNoFetchJoinWalker::class;
            $treeWalkers[] = EnsureOrderByWalker::class;
            $treeWalkers[] = EnsureMaxResultWalker::class;
        } else {
            $treeWalkers = [
                EnsureNoFetchJoinWalker::class,
                EnsureOrderByWalker::class,
                EnsureMaxResultWalker::class,
            ];
        }
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $treeWalkers);

        $sql = $query->getSQL();
        assert(is_string($sql));

        $this->cursorEncoder = new CursorEncoder(
            $sql,
            array_keys($discriminators),
        );

        $from = null === $cursor
            ? array_map(function (PageDiscriminator $discriminator) {
                return $discriminator->getDefaultValue();
            }, $discriminators)
            : $this->cursorEncoder->decode($cursor);

        $this->generator = $this->createGenerator(
            $query,
            $attrs,
            $from,
        );
    }

    /**
     * @param QueryInterface<ItemT>                                        $query
     * @param array<array-key,callable(mixed):(scalar|\DateTimeInterface)> $attrs
     * @param array<array-key,scalar|\DateTimeInterface>                   $from
     *
     * @return \Generator<int,PaginatorPage<ItemT>>
     */
    private function createGenerator(
        QueryInterface $query,
        array $attrs,
        array $from,
    ) {
        while (true) {
            foreach ($from as $param => $value) {
                $query->setParameter($param, $value);
            }

            $results = $query->execute();
            if (count($results) === 0) {
                return;
            }

            $items = [];
            foreach ($results as $result) {
                $from = array_map(function ($attr) use ($result) {
                    $value = $attr($result);

                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    return (string) $value;
                }, $attrs);

                $items[] = new PaginatorItem(
                    $this->cursorEncoder->encode($from),
                    $result,
                );
            }

            yield new PaginatorPage($items);
        }
    }

    /**
     * @param array<int,PageDiscriminator> $discriminators
     *
     * @return array<array-key,PageDiscriminator>
     */
    private function reindexDiscriminators(array $discriminators): array
    {
        $ret = [];
        foreach ($discriminators as $discriminator) {
            $ret[$discriminator->getQueryParamName()] = $discriminator;
        }

        return $ret;
    }
}
