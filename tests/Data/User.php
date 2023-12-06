<?php

namespace Mention\FastDoctrinePaginator\Tests\Data;

use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Mention\FastDoctrinePaginator\Tests\Data\Id as IdObject;

/**
 * @Entity
 */
class User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @Column(type="datetime")
     *
     * @var \DateTimeInterface
     */
    private $createdAt;

    public function __construct(int $id, \DateTimeInterface $createdAt)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdObject(): IdObject
    {
        return new IdObject($this->id);
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
