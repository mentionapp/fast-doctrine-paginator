# Fast Doctrine Paginator

This package provides a fast Doctrine paginator that's suitable for infinite
scrolling, batch jobs, and GraphQL/Relay pagination.

## Why use this paginator ?

### It is fast

What makes this paginator fast is that it relies on seek/keyset pagination
rather than limit/offset. Learn more at https://use-the-index-luke.com/no-offset.

In addition to that, it lets the user write its own optimised queries.

### Suitable for batch jobs

Batch jobs over Doctrine queries typically have two problems:

- The query returns too many results at once and exhausts the memory (even when
  using `iterate()`, because the query result is still buffered locally)
- The entities accumulate in the UnitOfWork and exhaust the memory

Using this paginator solves these two problems:

- By using a paginated query, we avoid fetching too many items at once
- By clearing the entity manager after each page, we keep the number of managed
  entities under control.

### Suitable for GraphQL/Relay

GraphQL/Relay style pagination requires fine-grained cursors that point to the
beginning and end of a page, as well as to each item in a page. This paginator
provides that.

A paginator instance is an iterator over pages of items. This allows to act
before and after every page, for example to clear an entity manager or to
manage transactions.

### Suitable for infinite scrolling

Every page will be as fast as the previous one.

## Why NOT use this paginator ?

This paginator is cursor-based, so it may not be suitable for you if you need to
display a numeric list of pages (although this could be emulated).

### Usage

Here is a typical example:

``` php
<?php

$query = $entityManager->createQuery('
    SELECT   u.id, u.name
    FROM     Acme\\Entity\\Users u
    WHERE    u.id > :idCursor
    ORDER    BY u.id ASC
');

// Max results per page
$query->setMaxResults(3);

$paginator = DoctrinePaginatorBuilder::new()
    ->setQuery($query)
    ->setDiscriminators([
        new PageDiscriminator('idCursor', 'getId'),
    ])
    ->build();

foreach ($paginator as $page) {
    foreach ($page() as $user) {
        // ...
    }
    $em->clear();
}
```

A user-defined query is used to fetch the results for one page of results, and
will be executed multiple times when fetching multiple pages. Pagination stops
when the query returns no results.

The number of elements per page is defined by calling `setMaxResults()` on the
query itself.

Pagination is keyset-based, rather than limit/offset-based: Instead of asking
for the Nth rows in a query, we ask for the rows that are upper than
the highest one of the previous page. When comparing rows, we only take into
account one or a few columns, that we call the discriminators. We use the
value of the discriminator columns of the last row of one page to create an
internal cursor that can be used to fetch the next page.

For this to work effectively and flawlessly, the following
conditions must be true:

- The column(s) used for discrimination must be unique (if it's not the case,
  a combination of multiple discriminators must be used)
- The query must be ordered by the discrimination columns
- The query must have a WHERE clause that selects only rows whose
  discriminators are higher than the higher discriminators of the previous
  page.

### About the query

The query must be a Doctrine Query object. It must have a defined number
of max results (`setMaxResults()`), because this defines the number of
items per page.

It must be ordered by the discriminator columns.

It must have a WHERE clause that selects only the rows whose
discriminators are higher than the ones of the previous page. The
paginator calls `setParameter()` on the query to set these values.

### Examples

Examples with a Users table:

+-------+--------------+------+-----+---------+----------------+
| Field | Type         | Null | Key | Default | Extra          |
+-------+--------------+------+-----+---------+----------------+
| id    | int(11)      | NO   | PRI | NULL    | auto_increment |
| name  | varchar(255) | NO   |     | NULL    |                |
+-------+--------------+------+-----+---------+----------------+

+----+---------+
| id | name    |
+----+---------+
|  1 | Jackson |
|  2 | Sophia  |
|  3 | Aiden   |
|  4 | Olivia  |
|  5 | Lucas   |
|  6 | Ava     |
+----+---------+

If we sort by id, we can use id as discriminator, because it's unique.

Note the ORDER clause, that orders by our discriminator.

Note the WHERE clause, that discriminates by our discriminator. We use the
query parameter :idCursor. The value of this parameter is automatically
set by the paginator. By default, it's set to `0` when requesting the
first page, and then it's automatically updated to the value found in the
last row of the latest fetched page.

``` php
<?php

$query = $entityManager->createQuery('
    SELECT   u.id, u.name
    FROM     Users u
    WHERE    u.id > :idCursor
    ORDER    BY u.id ASC
');

// Max results per page
$query->setMaxResults(3);

$paginator = DoctrinePaginatorBuilder::new()
    ->setQuery($query)
    ->setDiscriminators([
        new PageDiscriminator('idCursor', 'getId'),
    ])
    ->build();

foreach ($paginator as $page) {
    foreach ($page() as $result) {
        // ...
    }
    $em->clear();
}
```

The first page will return this:

```
+----+---------+
| id | name    |
+----+---------+
|  1 | Jackson |
|  2 | Sophia  |
|  3 | Aiden   |
+----+---------+
```

The paginator retains `id=3` as cursor internally. Before requesting the
next page, the paginator calls `setParameter('idCursor', 3)` on the query.
As expected, the second page returns this:

```
+----+--------+
| id | name   |
+----+--------+
|  4 | Olivia |
|  5 | Lucas  |
|  6 | Ava    |
+----+--------+
```

#### Sorting by name:

If we sort by name, we can not use it directly as discriminator, because
it's not unique. If we sort by name and id, we can use name and id as
discriminators, because they are unique together.

Notice how we use u.id as a fallback when the name equals to the current
name cursor.

``` php
<?php

$query = $entityManager->createQuery('
    SELECT   u.id, u.name
    FROM     Users u
    WHERE    u.name > :nameCursor
    OR       (u.name = :nameCursor AND u.id > :idCursor)
    ORDER    BY u.name ASC, u.id ASC
');

$paginator = (new DoctrinePaginatorBuilder())
    ->setQuery($query)
    ->setDiscriminators([
        new PageDiscriminator('nameCursor', 'getName'),
        new PageDiscriminator('idCursor', 'getId'),
    ])
    ->build();
```

### Resuming pagination

We can resume pagination on a new DoctrinePaginator instance by setting
the cursor explicitly. This is useful when paginating through multiple
requests, for example.

The end cursor of a page can be retrieved by calling `getCursor()` on a
PaginatorItem object. Using this cursor will fetch the next page.

A paginator that will resume at this position can be built by calling
`setCursor()` on a DoctrinePaginatorBuilder.

### Batch jobs

The paginator is particularly suitable for batching, because it allows to keep
the memory usage under control:

- The query returns a controlled amount of rows
- It gives an opportunity to act before and after every page

For example, the entity manager can be safely cleared between two pages:

``` php
<?php

foreach ($paginator as $page) {
    foreach ($page() as $result) {
        // ...
    }
    $em->clear();
}
```

### GraphQL/Relay

The paginator is particularly suitable for GraphQL/Relay pagination, since
it provides cursors for the pages and items.

- For pages: Use startCuror() / endCursor() on the PaginatorPageInterface.
- For items: Use getCursor() on the PaginatorPageItemInterface.

``` php
<?php

foreach ($paginator as $page) {
    $startCursor = $page->startCursor();
    $endCursor = $page->endCursor();
    foreach ($page->items() as $item) {
        $itemCursor = $item->getCursor();
    }
}
```