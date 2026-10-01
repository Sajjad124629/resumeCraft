<?php

namespace App\Repository;

use App\Entity\Attribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attribute>
 */
class AttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attribute::class);
    }

    /**
     * Fetch all attributes formatted as array for selection dropdowns.
     *
     * @return array<int, array{id: int, name: string, type: string}>
     */
    public function findAllForSelect(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id', 'a.name', 'a.type')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
