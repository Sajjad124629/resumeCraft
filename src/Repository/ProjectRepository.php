<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Get unique project tags
     */
    public function getTags(int $limit = 20): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.tags')
            ->where('p.tags IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $tags = [];
        foreach ($rows as $row) {
            if (!empty($row['tags']) && is_array($row['tags'])) {
                $tags = array_merge($tags, $row['tags']);
            }
        }
        $tagCounts = array_count_values($tags);
        arsort($tagCounts);
        return array_slice($tagCounts, 0, $limit);
    }
}
