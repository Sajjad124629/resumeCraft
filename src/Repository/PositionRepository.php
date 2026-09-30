<?php

namespace App\Repository;

use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }
    public function findLatestSummary(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.title', 'p.company', 'p.level')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findPopularSummary(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.cvs', 'c')
            ->select('p.id', 'p.title', 'p.company', 'COUNT(c.id) AS cvCount')
            ->groupBy('p.id')
            ->orderBy('cvCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
