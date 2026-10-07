<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\SupportTicketRepository;
use App\Service\PowerAutomateStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support-ticket')]
class SupportTicketController extends AbstractController
{
    public function __construct(
        private readonly PowerAutomateStorageService $storageService,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Provide contextual data for the support ticket modal:
     * - Current user info and role
     * - Registered administrators' emails
     * - Available position titles
     * - Dropbox storage status
     */
    #[Route('/context', name: 'app_support_ticket_context', methods: ['GET'])]
    public function context(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $adminEmails = $this->storageService->getAdminEmails();

        $userData = null;
        if ($user) {
            $userDetails = $user->getUserDetails();
            $fn = $userDetails?->getFirstName() ?? '';
            $ln = $userDetails?->getLastName() ?? '';
            $fullName = trim($fn . ' ' . $ln);
            if (empty($fullName)) {
                $fullName = $user->getUserIdentifier();
            }

            $roleName = $user->getRole()?->getName();
            if (!$roleName) {
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    $roleName = 'Administrator';
                } elseif (in_array('ROLE_RECRUITER', $user->getRoles(), true)) {
                    $roleName = 'Recruiter';
                } elseif (in_array('ROLE_CANDIDATE', $user->getRoles(), true)) {
                    $roleName = 'Candidate';
                } else {
                    $roleName = 'User';
                }
            }

            $userData = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'fullName' => $fullName,
                'roleName' => $roleName,
                'roles' => $user->getRoles(),
                'reportedByString' => sprintf('%s (%s) - %s', $fullName, $roleName, $user->getEmail()),
            ];
        }

        // Fetch position titles from database for dropdown selection
        $positions = [];
        try {
            $posEntities = $this->em->getRepository(Position::class)->findBy([], ['id' => 'DESC'], 30);
            foreach ($posEntities as $pos) {
                $positions[] = [
                    'id' => $pos->getId(),
                    'title' => $pos->getTitle(),
                ];
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }

        $hasDropboxToken = (!empty($_ENV['DROPBOX_ACCESS_TOKEN']) && $_ENV['DROPBOX_ACCESS_TOKEN'] !== 'your_dropbox_access_token_here')
            || (!empty($_ENV['DROPBOX_REFRESH_TOKEN']) && !empty($_ENV['DROPBOX_APP_KEY']) && !empty($_ENV['DROPBOX_APP_SECRET']));
        $dropboxAppKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? ''));

        return $this->json([
            'success' => true,
            'user' => $userData,
            'isLoggedIn' => $user !== null,
            'adminEmails' => $adminEmails,
            'positions' => $positions,
            'activeProvider' => 'dropbox',
            'hasDropboxToken' => $hasDropboxToken,
            'dropboxAppKey' => $dropboxAppKey,
        ]);
    }

    /**
     * Create support ticket:
     * Validates summary and priority, generates formatted JSON with all required fields,
     * stores in MySQL database, and uploads it via REST API to Dropbox for Power Automate.
     */
    #[Route('/create', name: 'app_support_ticket_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());

        $summary = trim((string)($payload['summary'] ?? ''));
        if (empty($summary)) {
            return $this->json([
                'success' => false,
                'error' => 'Ticket summary is required. Please describe your issue.',
            ], 400);
        }

        $priority = trim((string)($payload['priority'] ?? 'Average'));
        $validPriorities = ['High', 'Average', 'Low'];
        if (!in_array($priority, $validPriorities, true)) {
            $priority = 'Average';
        }

        $link = trim((string)($payload['link'] ?? $request->headers->get('referer', 'http://localhost')));
        $positionTitle = isset($payload['position']) ? trim((string)$payload['position']) : null;
        $reporterName = isset($payload['reporter_name']) ? trim((string)$payload['reporter_name']) : null;
        $reporterEmail = isset($payload['reporter_email']) ? trim((string)$payload['reporter_email']) : null;
        $customToken = isset($payload['token']) && trim((string)$payload['token']) !== '' ? trim((string)$payload['token']) : null;

        /** @var User|null $user */
        $user = $this->getUser();

        // Build exact required JSON structure (including Reporter Email & Role)
        $ticketData = $this->storageService->buildTicketData(
            $user,
            $summary,
            $priority,
            $link,
            $positionTitle,
            null,
            $reporterName,
            $reporterEmail
        );

        // Upload to Cloud (Dropbox)
        $uploadResult = $this->storageService->uploadTicket($ticketData, 'dropbox', $customToken);

        // Persist support ticket into Database
        try {
            $ticket = new SupportTicket();
            $ticket->setTicketId($ticketData['Ticket ID']);
            $ticket->setSummary($summary);
            $ticket->setPriority($ticketData['Priority']);
            $ticket->setStatus(SupportTicket::STATUS_OPEN);
            $ticket->setReportedBy($ticketData['Reported by']);
            $ticket->setReporterEmail($ticketData['Reporter Email'] ?? null);
            $ticket->setUser($user);
            $ticket->setPositionTitle($positionTitle && $positionTitle !== 'N/A' ? $positionTitle : null);
            $ticket->setPageLink($link);
            $ticket->setFileName($uploadResult['fileName']);
            $ticket->setCloudPath($uploadResult['cloudPath']);
            $ticket->setProvider('dropbox');
            $ticket->setIsSimulated((bool)($uploadResult['isSimulated'] ?? false));

            $this->em->persist($ticket);
            $this->em->flush();
        } catch (\Throwable $e) {
            // Log error but don't break upload response
        }

        return $this->json([
            'success' => true,
            'message' => $uploadResult['message'],
            'ticketId' => $uploadResult['ticketId'],
            'fileName' => $uploadResult['fileName'],
            'cloudPath' => $uploadResult['cloudPath'],
            'provider' => $uploadResult['provider'],
            'isSimulated' => $uploadResult['isSimulated'],
            'ticketData' => $uploadResult['ticketData'],
            'downloadUrl' => $this->generateUrl('app_support_ticket_download_file', ['filename' => $uploadResult['fileName']]),
        ]);
    }

    /**
     * Generate and immediately download the JSON file to user's computer.
     * Also records ticket in database.
     */
    #[Route('/download-json', name: 'app_support_ticket_download_json', methods: ['POST'])]
    public function downloadJson(Request $request): Response
    {
        $payload = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());

        $summary = trim((string)($payload['summary'] ?? 'Support ticket inquiry'));
        $priority = trim((string)($payload['priority'] ?? 'Average'));
        $link = trim((string)($payload['link'] ?? $request->headers->get('referer', 'http://localhost')));
        $positionTitle = isset($payload['position']) ? trim((string)$payload['position']) : null;
        $reporterName = isset($payload['reporter_name']) ? trim((string)$payload['reporter_name']) : null;
        $reporterEmail = isset($payload['reporter_email']) ? trim((string)$payload['reporter_email']) : null;

        /** @var User|null $user */
        $user = $this->getUser();

        $ticketData = $this->storageService->buildTicketData(
            $user,
            $summary,
            $priority,
            $link,
            $positionTitle,
            null,
            $reporterName,
            $reporterEmail
        );

        $jsonString = json_encode($ticketData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $fileName = sprintf('ticket_%s_%s.json', date('Ymd_His'), strtolower(substr($ticketData['Ticket ID'], -6)));

        // Persist ticket in DB as open
        try {
            $ticket = new SupportTicket();
            $ticket->setTicketId($ticketData['Ticket ID']);
            $ticket->setSummary($summary);
            $ticket->setPriority($ticketData['Priority']);
            $ticket->setStatus(SupportTicket::STATUS_OPEN);
            $ticket->setReportedBy($ticketData['Reported by']);
            $ticket->setReporterEmail($ticketData['Reporter Email'] ?? null);
            $ticket->setUser($user);
            $ticket->setPositionTitle($positionTitle && $positionTitle !== 'N/A' ? $positionTitle : null);
            $ticket->setPageLink($link);
            $ticket->setFileName($fileName);
            $ticket->setCloudPath('/SupportTickets/' . $fileName);
            $ticket->setProvider('dropbox');
            $ticket->setIsSimulated(false);

            $this->em->persist($ticket);
            $this->em->flush();
        } catch (\Throwable $e) {
        }

        $response = new Response($jsonString);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Download a previously generated ticket by filename or ticket ID.
     */
    #[Route('/download/{filename}', name: 'app_support_ticket_download_file', methods: ['GET'])]
    public function downloadFile(string $filename): Response
    {
        // Sanitize filename to prevent directory traversal
        $safeFilename = basename($filename);
        $filePath = $this->getParameter('kernel.project_dir') . '/var/support_tickets/' . $safeFilename;

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
        } else {
            $ticketRepo = $this->em->getRepository(SupportTicket::class);
            $cleanId = str_replace('.json', '', $safeFilename);
            /** @var SupportTicket|null $ticket */
            $ticket = $ticketRepo->findOneBy(['fileName' => $safeFilename])
                ?? $ticketRepo->findOneBy(['ticketId' => $cleanId])
                ?? $ticketRepo->findOneBy(['ticketId' => $safeFilename]);

            if (!$ticket) {
                throw $this->createNotFoundException('Support ticket file not found.');
            }

            $adminEmails = $this->storageService->getAdminEmails();

            $ticketData = [
                'Ticket ID' => $ticket->getTicketId(),
                'Summary' => $ticket->getSummary(),
                'Priority' => $ticket->getPriority(),
                'Reported by' => $ticket->getReportedBy(),
                'Reporter Name' => $ticket->getUser()?->getUserIdentifier() ?? 'User',
                'Reporter Email' => $ticket->getReporterEmail(),
                'Position' => $ticket->getPositionTitle() ?: 'N/A',
                'Link' => $ticket->getPageLink() ?: 'N/A',
                'Admins' => $adminEmails,
                'Admins\' e-mail addresses to use' => implode(', ', $adminEmails),
                'Application' => 'ResumeCraft',
                'Status' => $ticket->getStatus(),
                'Dropbox Path' => $ticket->getCloudPath() ?: ('/SupportTickets/' . $safeFilename),
                'Created at' => $ticket->getCreatedAt()?->format(\DateTimeInterface::ATOM) ?? date('c'),
            ];

            $content = json_encode($ticketData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $safeFilename
        );

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * List recently generated support tickets.
     */
    #[Route('/recent', name: 'app_support_ticket_recent', methods: ['GET'])]
    public function recent(): JsonResponse
    {
        $tickets = $this->storageService->listGeneratedTickets();

        return $this->json([
            'success' => true,
            'tickets' => array_slice($tickets, 0, 15),
        ]);
    }

    /**
     * Get Dropbox OAuth Authorization URL.
     */
    #[Route('/dropbox-auth-url', name: 'app_support_ticket_dropbox_auth_url', methods: ['GET'])]
    public function dropboxAuthUrl(): JsonResponse
    {
        $appKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? ''));
        if (empty($appKey)) {
            return $this->json([
                'success' => false,
                'error' => 'DROPBOX_APP_KEY is not configured in .env',
            ], 400);
        }

        // Offline token_access_type provides a long-lived refresh_token
        $authUrl = sprintf(
            'https://www.dropbox.com/oauth2/authorize?client_id=%s&response_type=code&token_access_type=offline',
            urlencode($appKey)
        );

        return $this->json([
            'success' => true,
            'authUrl' => $authUrl,
            'appKey' => $appKey,
        ]);
    }

    /**
     * Exchange Dropbox authorization code for refresh token and save it to .env.local.
     */
    #[Route('/dropbox-exchange-code', name: 'app_support_ticket_dropbox_exchange', methods: ['POST'])]
    public function dropboxExchangeCode(Request $request): JsonResponse
    {
        $payload = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());
        $code = trim((string)($payload['code'] ?? ''));

        if (empty($code)) {
            return $this->json([
                'success' => false,
                'error' => 'Authorization code is required.',
            ], 400);
        }

        $result = $this->storageService->exchangeDropboxAuthCode($code);
        if (!$result['success']) {
            return $this->json($result, 400);
        }

        // Automatically update .env.local if refresh_token or access_token received
        $envLocalPath = $this->getParameter('kernel.project_dir') . '/.env.local';
        if (file_exists($envLocalPath)) {
            $content = file_get_contents($envLocalPath);
            if (!empty($result['refresh_token'])) {
                $content = preg_replace('/^DROPBOX_REFRESH_TOKEN=.*$/m', 'DROPBOX_REFRESH_TOKEN=' . $result['refresh_token'], $content);
            }
            if (!empty($result['access_token'])) {
                $content = preg_replace('/^DROPBOX_ACCESS_TOKEN=.*$/m', 'DROPBOX_ACCESS_TOKEN=' . $result['access_token'], $content);
            }
            @file_put_contents($envLocalPath, $content);
        }

        return $this->json([
            'success' => true,
            'message' => 'Dropbox credentials successfully connected and stored!',
            'hasRefreshToken' => !empty($result['refresh_token']),
            'hasAccessToken' => !empty($result['access_token']),
        ]);
    }

    /**
     * Get ticket counts for admin badges and statistics.
     */
    #[Route('/stats', name: 'app_support_ticket_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var SupportTicketRepository $repo */
        $repo = $this->em->getRepository(SupportTicket::class);
        $counts = $repo->getStatusCounts();

        return $this->json([
            'success' => true,
            'stats' => $counts,
        ]);
    }

    /**
     * Get support tickets created by the current authenticated user.
     */
    #[Route('/my-tickets', name: 'app_support_ticket_my_tickets', methods: ['GET'])]
    public function myTickets(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'success' => false,
                'isLoggedIn' => false,
                'tickets' => [],
                'message' => 'Please log in to view your submitted support tickets.',
            ]);
        }

        /** @var SupportTicketRepository $repo */
        $repo = $this->em->getRepository(SupportTicket::class);
        $tickets = $repo->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            50
        );

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
                'adminNotes' => $ticket->getAdminNotes(),
                'createdAt' => $ticket->getCreatedAt()?->format('M d, Y h:i A'),
                'solvedAt' => $ticket->getSolvedAt()?->format('M d, Y h:i A'),
                'downloadUrl' => $ticket->getFileName() ? $this->generateUrl('app_support_ticket_download_file', ['filename' => $ticket->getFileName()]) : null,
            ];
        }, $tickets);

        return $this->json([
            'success' => true,
            'isLoggedIn' => true,
            'tickets' => $formatted,
            'total' => count($formatted),
        ]);
    }
}

