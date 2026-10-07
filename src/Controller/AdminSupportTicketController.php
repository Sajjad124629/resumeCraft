<?php

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Repository\SupportTicketRepository;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/support-tickets')]
#[IsGranted('ROLE_ADMIN')]
class AdminSupportTicketController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupportTicketRepository $ticketRepo,
        private readonly InertiaService $inertia
    ) {}

    /**
     * Admin view of all support tickets with filtering, status tracking, and KPI counts.
     */
    #[Route('', name: 'app_admin_support_tickets', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $search = trim((string)$request->query->get('search', ''));
        $status = trim((string)$request->query->get('status', 'all'));
        $priority = trim((string)$request->query->get('priority', 'all'));
        $sort = trim((string)$request->query->get('sort', 'createdAt'));
        $dir = strtolower((string)$request->query->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $result = $this->ticketRepo->findWithFilters(
            $search,
            $status,
            $priority,
            $page,
            $limit,
            $sort,
            $dir
        );

        $counts = $this->ticketRepo->getStatusCounts();

        // Format items for frontend
        $formattedTickets = array_map(function (SupportTicket $ticket) {
            return [
                'id' => $ticket->getId(),
                'ticketId' => $ticket->getTicketId(),
                'summary' => $ticket->getSummary(),
                'priority' => $ticket->getPriority(),
                'status' => $ticket->getStatus(),
                'reportedBy' => $ticket->getReportedBy(),
                'reporterEmail' => $ticket->getReporterEmail(),
                'positionTitle' => $ticket->getPositionTitle(),
                'pageLink' => $ticket->getPageLink(),
                'fileName' => $ticket->getFileName(),
                'cloudPath' => $ticket->getCloudPath(),
                'provider' => $ticket->getProvider(),
                'isSimulated' => $ticket->isSimulated(),
                'adminNotes' => $ticket->getAdminNotes(),
                'createdAt' => $ticket->getCreatedAt()?->format('Y-m-d H:i:s'),
                'solvedAt' => $ticket->getSolvedAt()?->format('Y-m-d H:i:s'),
                'downloadUrl' => $this->generateUrl('app_support_ticket_download_file', [
                    'filename' => $ticket->getFileName() ?: ($ticket->getTicketId() . '.json'),
                ]),
            ];
        }, $result['items']);

        return $this->inertia->render('admin/SupportTickets/Index', [
            'tickets' => $formattedTickets,
            'statusCounts' => $counts,
            'totalRows' => $result['total'],
            'currentPage' => $page,
            'pageSize' => $limit,
            'search' => $search,
            'statusFilter' => $status,
            'priorityFilter' => $priority,
            'sort' => $sort,
            'sortDir' => $dir,
        ]);
    }

    /**
     * Update ticket status (open, in_progress, solved) and optional admin note.
     */
    #[Route('/{id}/status', name: 'app_admin_support_ticket_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $ticket = $this->ticketRepo->find($id);
        if (!$ticket) {
            $this->addFlash('error', 'Ticket not found.');
            return $this->redirectToRoute('app_admin_support_tickets');
        }

        $payload = json_decode($request->getContent(), true) ?? $request->request->all();
        $newStatus = trim((string)($payload['status'] ?? ''));
        $adminNotes = isset($payload['adminNotes']) ? trim((string)$payload['adminNotes']) : null;

        $validStatuses = [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_SOLVED];
        if (!in_array($newStatus, $validStatuses, true)) {
            $this->addFlash('error', 'Invalid ticket status.');
            return $this->redirectToRoute('app_admin_support_tickets');
        }

        $ticket->setStatus($newStatus);
        if ($adminNotes !== null) {
            $ticket->setAdminNotes($adminNotes);
        }

        $this->em->flush();

        $statusLabel = match ($newStatus) {
            SupportTicket::STATUS_SOLVED => 'Solved',
            SupportTicket::STATUS_IN_PROGRESS => 'In Progress',
            default => 'Open',
        };

        if ($request->headers->get('X-Inertia') || $request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => "Ticket {$ticket->getTicketId()} marked as {$statusLabel}.",
                'ticket' => [
                    'id' => $ticket->getId(),
                    'status' => $ticket->getStatus(),
                    'solvedAt' => $ticket->getSolvedAt()?->format('Y-m-d H:i:s'),
                ],
                'statusCounts' => $this->ticketRepo->getStatusCounts(),
            ]);
        }

        $this->addFlash('success', "Ticket {$ticket->getTicketId()} marked as {$statusLabel}.");
        return $this->redirectToRoute('app_admin_support_tickets');
    }

    /**
     * Delete a support ticket.
     */
    #[Route('/{id}', name: 'app_admin_support_ticket_delete', methods: ['DELETE', 'POST'])]
    public function delete(int $id, Request $request): Response
    {
        $ticket = $this->ticketRepo->find($id);
        if (!$ticket) {
            $this->addFlash('error', 'Ticket not found.');
            return $this->redirectToRoute('app_admin_support_tickets');
        }

        $ticketId = $ticket->getTicketId();
        $this->em->remove($ticket);
        $this->em->flush();

        if ($request->headers->get('X-Inertia') || $request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => "Ticket {$ticketId} deleted.",
                'statusCounts' => $this->ticketRepo->getStatusCounts(),
            ]);
        }

        $this->addFlash('success', "Ticket {$ticketId} has been deleted.");
        return $this->redirectToRoute('app_admin_support_tickets');
    }
}

