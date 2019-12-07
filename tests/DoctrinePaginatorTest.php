<?php

namespace Mention\FastDoctrinePaginator\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mention\FastDoctrinePaginator\DoctrinePaginator;
use Mention\FastDoctrinePaginator\DoctrinePaginatorBuilder;
use Mention\FastDoctrinePaginator\PageDiscriminator;
use Mention\FastDoctrinePaginator\Tests\Data\User;
use PHPUnit\Framework\TestCase;

class DoctrinePaginatorTest extends TestCase
{
    private const DB = __DIR__.'/Data/db.sqlite';

    protected function tearDown(): void
    {
        if (file_exists(self::DB)) {
            unlink(self::DB);
        }

        \Mockery::close();
    }

    public function testIterationWithArrayHydratation(): void
    {
        $em = $this->createEntityManager();

        $this->insertMulti(
            $em,
            'User',
            [
                ['id' => 2, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 3, 'createdAt' => '2018-01-02 00:00:00'],
                ['id' => 4, 'createdAt' => '2018-01-03 00:00:00'],
                ['id' => 5, 'createdAt' => '2018-01-04 00:00:00'],
            ]
        );

        $query = $em->createQuery('
            SELECT u.id, u.createdAt
            FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
            WHERE  u.id > :id
            ORDER  BY u.id
        ');

        $query->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'id'),
            ])
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->build();

        $pages = iterator_to_array($paginator->getIterator());

        self::assertCount(2, $pages);

        self::assertEquals(
            [
                ['id' => 2, 'createdAt' => new \DateTime('2018-01-01')],
                ['id' => 3, 'createdAt' => new \DateTime('2018-01-02')],
            ],
            $pages[0]()
        );

        self::assertEquals(
            [
                ['id' => 4, 'createdAt' => new \DateTime('2018-01-03')],
                ['id' => 5, 'createdAt' => new \DateTime('2018-01-04')],
            ],
            $pages[1]()
        );
    }

    public function testIterationWithObjectHydratation(): void
    {
        $em = $this->createEntityManager();

        $this->insertMulti(
            $em,
            'User',
            [
                ['id' => 2, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 3, 'createdAt' => '2018-01-02 00:00:00'],
                ['id' => 4, 'createdAt' => '2018-01-03 00:00:00'],
                ['id' => 5, 'createdAt' => '2018-01-04 00:00:00'],
            ]
        );

        $query = $em->createQuery('
            SELECT u
            FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
            WHERE  u.id > :id
            ORDER  BY u.id
        ');

        $query->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->setHydrationMode(Query::HYDRATE_OBJECT)
            ->build();

        $pages = iterator_to_array($paginator->getIterator());

        self::assertCount(2, $pages);

        self::assertEquals(
            [
                new User(2, new \DateTime('2018-01-01')),
                new User(3, new \DateTime('2018-01-02')),
            ],
            $pages[0]()
        );

        self::assertEquals(
            [
                new User(4, new \DateTime('2018-01-03')),
                new User(5, new \DateTime('2018-01-04')),
            ],
            $pages[1]()
        );
    }

    public function testResumeAtCursor(): void
    {
        $em = $this->createEntityManager();

        $this->insertMulti(
            $em,
            'User',
            [
                ['id' => 2, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 3, 'createdAt' => '2018-01-02 00:00:00'],
                ['id' => 4, 'createdAt' => '2018-01-03 00:00:00'],
                ['id' => 5, 'createdAt' => '2018-01-04 00:00:00'],
            ]
        );

        $dql = '
            SELECT u
            FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
            WHERE  u.id > :id
            ORDER  BY u.id, u.createdAt
        ';

        $query = $em->createQuery($dql)->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->setHydrationMode(Query::HYDRATE_OBJECT)
            ->build();

        $pages = iterator_to_array($paginator->getIterator());

        $cursor = $pages[0]->lastCursor();

        $query = $em->createQuery($dql)->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->setHydrationMode(Query::HYDRATE_OBJECT)
            ->setCursor($cursor)
            ->build();

        $pages = iterator_to_array($paginator->getIterator());

        self::assertCount(1, $pages);

        self::assertEquals(
            [
                new User(4, new \DateTime('2018-01-03')),
                new User(5, new \DateTime('2018-01-04')),
            ],
            $pages[0]()
        );
    }

    public function testCompoundDiscriminator(): void
    {
        $em = $this->createEntityManager();

        $this->insertMulti(
            $em,
            'User',
            [
                ['id' => 4, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 5, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 2, 'createdAt' => '2018-01-01 00:00:00'],
                ['id' => 1, 'createdAt' => '2018-01-04 00:00:00'],
            ]
        );

        $query = $em->createQuery('
            SELECT u.id, u.createdAt
            FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
            WHERE  u.createdAt > :created_at
            OR     (u.createdAt = :created_at AND u.id > :id)
            ORDER  BY u.createdAt, u.id
        ');

        $query->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('created_at', 'createdAt'),
                new PageDiscriminator('id', 'id'),
            ])
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->build();

        $pages = iterator_to_array($paginator->getIterator());

        self::assertCount(2, $pages);

        self::assertEquals(
            [
                ['id' => 2, 'createdAt' => new \DateTime('2018-01-01')],
                ['id' => 4, 'createdAt' => new \DateTime('2018-01-01')],
            ],
            $pages[0]()
        );

        self::assertEquals(
            [
                ['id' => 5, 'createdAt' => new \DateTime('2018-01-01')],
                ['id' => 1, 'createdAt' => new \DateTime('2018-01-04')],
            ],
            $pages[1]()
        );
    }

    public function testDiscriminatorsMustBeGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DoctrinePaginator must have at least one discriminator');

        $em = $this->createEntityManager();
        $query = $em
            ->createQuery('
                SELECT u
                FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
                ORDER  BY u.id
            ')
            ->setMaxResults(2);

        new DoctrinePaginator($query, []);
    }

    /**
     * @dataProvider dataQueryMustBeOrdered
     */
    public function testQueryMustBeOrdered(string $dql): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The pagination query must have an ORDER BY clause');

        $em = $this->createEntityManager();

        $query = $em->createQuery($dql)->setMaxResults(2);

        DoctrinePaginatorBuilder::new()
            ->setQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'id'),
            ])
            ->build();
    }

    /**
     * @phpstan-return array<string,array{string}>
     */
    public function dataQueryMustBeOrdered(): array
    {
        return [
            'simple query' => ['
                SELECT u
                FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
            '],
            'ordered sub query' => ['
                SELECT u
                FROM   Mention\FastDoctrinePaginator\Tests\Data\User u
                WHERE  u.id IN (
                    SELECT u2.id
                    FROM   Mention\FastDoctrinePaginator\Tests\Data\User u2
                    ORDER  BY u2.id ASC
                )
            '],
        ];
    }

    private function createEntityManager(): EntityManager
    {
        if (file_exists(self::DB)) {
            unlink(self::DB);
        }

        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__.'/Data'],
            true,
            null,
            null,
            false
        );

        $conn = [
            'driver' => 'pdo_sqlite',
            'path' => self::DB,
        ];

        $em = EntityManager::create($conn, $config);

        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadatas);

        return $em;
    }

    /**
     * @phpstan-param array<array<string,mixed>> $rows
     */
    private function insertMulti(EntityManager $em, string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $em->getConnection()->insert($table, $row);
        }
    }
}
