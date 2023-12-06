<?php

namespace Mention\FastDoctrinePaginator\Tests;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mention\FastDoctrinePaginator\DoctrinePaginator;
use Mention\FastDoctrinePaginator\DoctrinePaginatorBuilder;
use Mention\FastDoctrinePaginator\Internal\TypedQuery;
use Mention\FastDoctrinePaginator\PageDiscriminator;
use Mention\FastDoctrinePaginator\Tests\Data\Id;
use Mention\FastDoctrinePaginator\Tests\Data\User;
use Mention\WebBundle\Tests\WebTestCase;
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

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'id'),
            ])
            ->withHydrationMode(Query::HYDRATE_ARRAY)
            ->build();

        $pages = [...$paginator];

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

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->withHydrationMode(Query::HYDRATE_OBJECT)
            ->build();

        $pages = [...$paginator];

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

    public function testCustomDiscriminatorValueMapper(): void
    {
        DoctrinePaginator::setDiscriminatorValueMapper(function ($value) {
            if ($value instanceof Id) {
                return $value->value;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            assert($value instanceof \Stringable);

            return (string) $value;
        });

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

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getIdObject'),
            ])
            ->withHydrationMode(Query::HYDRATE_OBJECT)
            ->build();

        $pages = [...$paginator];

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

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->withHydrationMode(Query::HYDRATE_OBJECT)
            ->build();

        $pages = [...$paginator];

        $cursor = $pages[0]->lastCursor();

        $query = $em->createQuery($dql)->setMaxResults(2);

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'getId'),
            ])
            ->withHydrationMode(Query::HYDRATE_OBJECT)
            ->setCursor($cursor)
            ->build();

        $pages = [...$paginator];

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

        $paginator = DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('created_at', 'createdAt'),
                new PageDiscriminator('id', 'id'),
            ])
            ->withHydrationMode(Query::HYDRATE_ARRAY)
            ->build();

        $pages = [...$paginator];

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

        new DoctrinePaginator(new TypedQuery($query), []);
    }

    /**
     * @dataProvider             invalidQueryProvider
     */
    public function testQueryMustBeOrdered(string $dql): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The pagination query must have an ORDER BY clause');

        $em = $this->createEntityManager();

        $query = $em->createQuery($dql)->setMaxResults(2);

        DoctrinePaginatorBuilder::fromQuery($query)
            ->setDiscriminators([
                new PageDiscriminator('id', 'id'),
            ])
            ->build();
    }

    /**
     * @return array<array-key,array{string}>
     */
    public static function invalidQueryProvider(): array
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
        return require __DIR__ . '/object-manager.php';
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
