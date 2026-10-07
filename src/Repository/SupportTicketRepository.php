<?php

namespace App\Repository;

use App\Entity\SupportTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportTicket>
 */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /**
     * Get ticket status counts for admin sidebar and dashboard KPI cards.
     *
     * @return array{total: int, open: int, in_progress: int, solved: int}
     */
    public function getStatusCounts(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'solved' THEN 1 ELSE 0 END) as solved_count
            FROM support_ticket
        ";

        try {
            $stmt = $conn->executeQuery($sql);
            $row = $stmt->fetchAssociative();

            return [
                'total' => (int)($row['total'] ?? 0),
                'open' => (int)($row['open_count'] ?? 0),
                'in_progress' => (int)($row['in_progress_count'] ?? 0),
                'solved' => (int)($row['solved_count'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return [
                'total' => 0,
                'open' => 0,
                'in_progress' => 0,
                'solved' => 0,
            ];
        }
    }

    /**
     * Search and filter support tickets with pagination.
     */
    public function findWithFilters(
        ?string $search = null,
        ?string $status = null,
        ?string $priority = null,
        int $page = 1,
        int $limit = 15,
        string $sort = 'createdAt',
        string $dir = 'desc'
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u');

        if (!empty($search)) {
            $term = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(t.ticketId) LIKE :term OR LOWER(t.summary) LIKE :term OR LOWER(t.reportedBy) LIKE :term OR LOWER(t.reporterEmail) LIKE :term OR LOWER(t.positionTitle) LIKE :term')
               ->setParameter('term', $term);
        }

        if (!empty($status) && $status !== 'all') {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', strtolower($status));
        }

        if (!empty($priority) && $priority !== 'all') {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', ucfirst(strtolower($priority)));
        }

        // Clone for counting total
        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        // Allowed sort columns
        $validSorts = [
            'id' => 't.id',
            'ticketId' => 't.ticketId',
            'priority' => 't.priority',
            'status' => 't.status',
            'reportedBy' => 't.reportedBy',
            'createdAt' => 't.createdAt',
            'solvedAt' => 't.solvedAt',
        ];

        $sortField = $validSorts[$sort] ?? 't.createdAt';
        $direction = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $items = $qb->orderBy($sortField, $direction)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / max(1, $limit)),
        ];
    }
}

