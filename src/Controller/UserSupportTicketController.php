<?php

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\SupportTicketRepository;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-tickets')]
#[IsGranted('ROLE_USER')]
class UserSupportTicketController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupportTicketRepository $ticketRepo,
        private readonly InertiaService $inertia
    ) {}

    /**
     * User's My Tickets page displaying their submitted support tickets,
     * status tracking, and resolution details using Vue3Datatable.
     */
    #[Route('', name: 'app_my_tickets', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $search = trim((string)$request->query->get('search', ''));
        $status = trim((string)$request->query->get('status', 'all'));
        $priority = trim((string)$request->query->get('priority', 'all'));
        $sort = trim((string)$request->query->get('sort', 'createdAt'));
        $dir = strtolower((string)$request->query->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $qb = $this->ticketRepo->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if (!empty($search)) {
            $term = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(t.ticketId) LIKE :term OR LOWER(t.summary) LIKE :term OR LOWER(t.positionTitle) LIKE :term')
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

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        $validSorts = [
            'id' => 't.id',
            'ticketId' => 't.ticketId',
            'priority' => 't.priority',
            'status' => 't.status',
            'positionTitle' => 't.positionTitle',
            'createdAt' => 't.createdAt',
        ];
        $sortField = $validSorts[$sort] ?? 't.createdAt';
        $direction = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $items = $qb->orderBy($sortField, $direction)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $formatted = array_map(function (SupportTicket $ticket) {
            return [
                'id' => $ticket->getId(),
                'ticketId' => $ticket->getTicketId(),
                'summary' => $ticket->getSummary(),
                'priority' => $ticket->getPriority(),
                'status' => $ticket->getStatus(),
                'positionTitle' => $ticket->getPositionTitle(),
                'pageLink' => $ticket->getPageLink(),
                'fileName' => $ticket->getFileName(),
                'cloudPath' => $ticket->getCloudPath(),
                'adminNotes' => $ticket->getAdminNotes(),
                'createdAt' => $ticket->getCreatedAt()?->format('Y-m-d H:i:s'),
                'solvedAt' => $ticket->getSolvedAt()?->format('Y-m-d H:i:s'),
                'downloadUrl' => $this->generateUrl('app_support_ticket_download_file', [
                    'filename' => $ticket->getFileName() ?: ($ticket->getTicketId() . '.json'),
                ]),
            ];
        }, $items);

        // Calculate personal ticket counts for quick filter tabs
        $conn = $this->em->getConnection();
        $countRow = $conn->executeQuery("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'solved' THEN 1 ELSE 0 END) as solved_count
            FROM support_ticket
            WHERE user_id = :userId
        ", ['userId' => $user->getId()])->fetchAssociative();

        $userCounts = [
            'total' => (int)($countRow['total'] ?? 0),
            'open' => (int)($countRow['open_count'] ?? 0),
            'in_progress' => (int)($countRow['in_progress_count'] ?? 0),
            'solved' => (int)($countRow['solved_count'] ?? 0),
        ];

        return $this->inertia->render('support/MyTickets', [
            'tickets' => $formatted,
            'userCounts' => $userCounts,
            'totalRows' => $total,
            'currentPage' => $page,
            'pageSize' => $limit,
            'search' => $search,
            'statusFilter' => $status,
            'priorityFilter' => $priority,
            'sort' => $sort,
            'sortDir' => $dir,
        ]);
    }
}

