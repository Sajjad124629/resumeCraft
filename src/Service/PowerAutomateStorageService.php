<?php

namespace App\Service;

use App\Entity\Position;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class PowerAutomateStorageService
{
    private string $storageDir;
    private ?string $cachedDropboxAccessToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        $this->storageDir = $this->projectDir . '/var/support_tickets';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0777, true);
        }
    }

    /**
     * Get all administrators' email addresses registered in the application.
     *
     * @return string[]
     */
    public function getAdminEmails(): array
    {
        $emails = [];

        try {
            $userRepo = $this->em->getRepository(User::class);
            $users = $userRepo->findAll();

            foreach ($users as $user) {
                if (in_array('ROLE_ADMIN', $user->getRoles(), true) || $user->getRole()?->getSlug() === 'ROLE_ADMIN') {
                    $email = trim((string)$user->getEmail());
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $email;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to query admin users for support tickets: ' . $e->getMessage());
        }

        // Add system fallback admin email if configured in environment
        $fallbackAdmin = $_SERVER['SUPPORT_TICKET_ADMIN_EMAIL'] ?? $_ENV['SUPPORT_TICKET_ADMIN_EMAIL'] ?? $_SERVER['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? '';
        if (!empty($fallbackAdmin)) {
            $parts = preg_split('/[,;]+/', $fallbackAdmin);
            foreach (array_reverse($parts) as $part) {
                $trimmed = trim($part);
                if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                    array_unshift($emails, $trimmed);
                }
            }
        }

        // If we have real external emails configured, filter out dummy demo domain to prevent delivery bounce
        $filtered = array_values(array_unique($emails));
        $realEmails = array_filter($filtered, fn($e) => !str_ends_with($e, '@resumecraft.com'));

        if (!empty($realEmails)) {
            return array_values($realEmails);
        }

        // Ensure default admin from seed is included if completely empty
        if (empty($filtered)) {
            $filtered[] = 'admin@resumecraft.com';
        }

        return $filtered;
    }

    /**
     * Build the structured JSON payload according to the exact project specification.
     *
     * Fields required:
     * - "Reported by": current user with added user role
     * - "Position": title of corresponding position if applicable
     * - "Link": link to the page from which user invoked ticket creation
     * - "Priority": one from "High", "Average", "Low"
     * - Admins' e-mail addresses to use
     * - Summary
     */
    public function buildTicketData(
        ?User $user,
        string $summary,
        string $priority,
        string $link,
        ?string $positionTitle = null,
        ?array $customAdminEmails = null,
        ?string $reporterName = null,
        ?string $reporterEmail = null
    ): array {
        // Normalize priority: strictly one of "High", "Average", "Low"
        $normalizedPriority = match (strtolower(trim($priority))) {
            'high' => 'High',
            'low' => 'Low',
            default => 'Average',
        };

        // Determine "Reported by" (Current user with added user role + email)
        if ($user) {
            $userDetails = $user->getUserDetails();
            $firstName = $userDetails?->getFirstName() ?? '';
            $lastName = $userDetails?->getLastName() ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
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
            $finalEmail = (string)$user->getEmail();
            $reportedBy = sprintf('%s (%s) - %s', $fullName, $roleName, $finalEmail);
        } else {
            $guestName = !empty($reporterName) ? trim($reporterName) : 'Guest User';
            $guestEmail = !empty($reporterEmail) ? trim($reporterEmail) : 'guest@resumecraft.com';
            $roleName = 'Guest';
            $finalEmail = $guestEmail;
            $fullName = $guestName;
            $reportedBy = sprintf('%s (Guest) - %s', $guestName, $guestEmail);
        }

        // Determine "Position" (Title of corresponding position if applicable)
        $position = !empty($positionTitle) && trim($positionTitle) !== '' ? trim($positionTitle) : 'N/A';

        // Clean link
        $cleanedLink = !empty($link) ? trim($link) : 'http://localhost';

        // Gather admin email addresses
        $adminEmails = !empty($customAdminEmails) ? $customAdminEmails : $this->getAdminEmails();

        $ticketId = sprintf('TICK-%s-%s', date('Ymd'), strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        $createdAt = (new \DateTimeImmutable())->format('c');

        return [
            'Ticket ID' => $ticketId,
            'Summary' => trim($summary),
            'Priority' => $normalizedPriority,
            'Reported by' => $reportedBy,
            'Reporter Name' => $fullName,
            'Reporter Role' => $roleName,
            'Reporter Email' => $finalEmail,
            'Position' => $position,
            'Link' => $cleanedLink,
            'Admins' => $adminEmails,
            'Admins\' e-mail addresses to use' => implode('; ', $adminEmails),
            'Created at' => $createdAt,
            'Application' => 'ResumeCraft',
        ];
    }

    /**
     * Upload the ticket JSON file to Dropbox.
     * Also saves a local copy in var/support_tickets/ for verification and instant access.
     */
    public function uploadTicket(array $ticketData, ?string $provider = 'dropbox', ?string $customToken = null): array
    {
        $selectedProvider = 'dropbox';
        $ticketId = $ticketData['Ticket ID'] ?? ('TICK-' . date('YmdHis'));
        $safeTicketId = preg_replace('/[^a-zA-Z0-9_-]/', '', $ticketId);
        $fileName = sprintf('ticket_%s_%s.json', date('Ymd_His'), $safeTicketId);

        $jsonContent = json_encode($ticketData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Always save a local copy
        $localPath = $this->saveLocalCopy($fileName, $jsonContent);

        // Upload to Dropbox API
        $cloudResult = $this->uploadToDropbox($fileName, $jsonContent, $customToken);

        return [
            'success' => true,
            'provider' => $selectedProvider,
            'fileName' => $fileName,
            'localPath' => $localPath,
            'cloudPath' => $cloudResult['path'] ?? ('/SupportTickets/' . $fileName),
            'cloudId' => $cloudResult['id'] ?? null,
            'isSimulated' => $cloudResult['isSimulated'] ?? false,
            'message' => $cloudResult['message'] ?? 'File uploaded successfully to Dropbox',
            'ticketData' => $ticketData,
            'ticketId' => $ticketId,
        ];
    }

    /**
     * Fetch a brand new access token from Dropbox using permanent refresh token.
     */
    public function refreshDropboxAccessToken(): ?string
    {
        $refreshToken = trim((string)($_ENV['DROPBOX_REFRESH_TOKEN'] ?? ''));
        $appKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? ''));
        $appSecret = trim((string)($_ENV['DROPBOX_APP_SECRET'] ?? ''));

        if (!empty($refreshToken) && !empty($appKey) && !empty($appSecret)) {
            try {
                $response = $this->httpClient->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($appKey . ':' . $appSecret),
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $refreshToken,
                    ],
                    'timeout' => 15,
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray(false);
                    $newAccessToken = $data['access_token'] ?? null;
                    if ($newAccessToken) {
                        $this->cachedDropboxAccessToken = $newAccessToken;
                        $_ENV['DROPBOX_ACCESS_TOKEN'] = $newAccessToken;
                        $_SERVER['DROPBOX_ACCESS_TOKEN'] = $newAccessToken;
                        return $newAccessToken;
                    }
                }
                $this->logger->error('Failed to refresh Dropbox token: ' . $response->getContent(false));
            } catch (\Throwable $e) {
                $this->logger->error('Dropbox refresh token exception: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Resolve Dropbox access token:
     * 1. Custom token provided in request
     * 2. In-memory cached fresh token
     * 3. Auto-refresh token exchange using DROPBOX_REFRESH_TOKEN + DROPBOX_APP_KEY + DROPBOX_APP_SECRET
     * 4. Static DROPBOX_ACCESS_TOKEN from .env (fallback)
     */
    public function resolveDropboxAccessToken(?string $customToken = null): ?string
    {
        if (!empty($customToken)) {
            return trim($customToken);
        }

        if (!empty($this->cachedDropboxAccessToken)) {
            return $this->cachedDropboxAccessToken;
        }

        $refreshToken = trim((string)($_ENV['DROPBOX_REFRESH_TOKEN'] ?? ''));
        $appKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? ''));
        $appSecret = trim((string)($_ENV['DROPBOX_APP_SECRET'] ?? ''));

        if (!empty($refreshToken) && !empty($appKey) && !empty($appSecret)) {
            $fresh = $this->refreshDropboxAccessToken();
            if ($fresh) {
                return $fresh;
            }
        }

        $token = trim((string)($_ENV['DROPBOX_ACCESS_TOKEN'] ?? ''));
        if (!empty($token) && $token !== 'your_dropbox_access_token_here') {
            return $token;
        }

        return null;
    }

    /**
     * Exchange Dropbox authorization code for access token and refresh token.
     */
    public function exchangeDropboxAuthCode(string $code, ?string $redirectUri = null): array
    {
        $appKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? ''));
        $appSecret = trim((string)($_ENV['DROPBOX_APP_SECRET'] ?? ''));

        if (empty($appKey) || empty($appSecret)) {
            return ['success' => false, 'error' => 'DROPBOX_APP_KEY or DROPBOX_APP_SECRET is not configured in .env'];
        }

        try {
            $body = [
                'grant_type' => 'authorization_code',
                'code' => trim($code),
            ];
            if ($redirectUri) {
                $body['redirect_uri'] = $redirectUri;
            }

            $response = $this->httpClient->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($appKey . ':' . $appSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $body,
                'timeout' => 15,
            ]);

            $data = $response->toArray(false);
            if ($response->getStatusCode() === 200 && !empty($data['access_token'])) {
                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires_in' => $data['expires_in'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $data['error_description'] ?? ($data['error'] ?? 'Dropbox OAuth exchange failed with HTTP ' . $response->getStatusCode()),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload JSON file via Dropbox API v2.
     * Endpoint: POST https://content.dropboxapi.com/2/files/upload
     */
    private function uploadToDropbox(string $fileName, string $jsonContent, ?string $customToken = null): array
    {
        $token = $this->resolveDropboxAccessToken($customToken);
        $folder = trim((string)($_ENV['DROPBOX_FOLDER'] ?? '/SupportTickets'));
        $folder = '/' . trim($folder, '/');
        $cloudPath = $folder . '/' . $fileName;

        if (empty($token) || $token === 'your_dropbox_access_token_here') {
            $this->logger->info('Dropbox token not set in environment. Stored ticket in local storage and simulated cloud upload for Power Automate testing.');
            return [
                'path' => $cloudPath,
                'id' => 'simulated_dropbox_' . uniqid(),
                'isSimulated' => true,
                'message' => 'JSON ticket generated and saved locally in var/support_tickets/. (To automatically upload to cloud and trigger Power Automate, set DROPBOX_ACCESS_TOKEN or DROPBOX_REFRESH_TOKEN in .env or enter token in the modal)',
            ];
        }

        try {
            $response = $this->httpClient->request('POST', 'https://content.dropboxapi.com/2/files/upload', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Dropbox-API-Arg' => json_encode([
                        'path' => $cloudPath,
                        'mode' => 'add',
                        'autorename' => true,
                        'mute' => false,
                    ], JSON_UNESCAPED_SLASHES),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => $jsonContent,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                $data = $response->toArray(false);
                return [
                    'path' => $data['path_display'] ?? $cloudPath,
                    'id' => $data['id'] ?? null,
                    'isSimulated' => false,
                    'message' => 'Successfully uploaded JSON ticket file to Dropbox folder: ' . ($data['path_display'] ?? $cloudPath),
                ];
            }

            if ($statusCode === 401) {
                $this->logger->info('Dropbox access token expired (401). Attempting automatic refresh...');
                $freshToken = $this->refreshDropboxAccessToken();
                if ($freshToken) {
                    $retryResponse = $this->httpClient->request('POST', 'https://content.dropboxapi.com/2/files/upload', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $freshToken,
                            'Dropbox-API-Arg' => json_encode([
                                'path' => $cloudPath,
                                'mode' => 'add',
                                'autorename' => true,
                                'mute' => false,
                            ], JSON_UNESCAPED_SLASHES),
                            'Content-Type' => 'application/octet-stream',
                        ],
                        'body' => $jsonContent,
                        'timeout' => 30,
                    ]);

                    $retryStatus = $retryResponse->getStatusCode();
                    if ($retryStatus >= 200 && $retryStatus < 300) {
                        $data = $retryResponse->toArray(false);
                        return [
                            'path' => $data['path_display'] ?? $cloudPath,
                            'id' => $data['id'] ?? null,
                            'isSimulated' => false,
                            'message' => 'Successfully uploaded JSON ticket file to Dropbox folder: ' . ($data['path_display'] ?? $cloudPath),
                        ];
                    }
                }
            }

            $errorContent = $response->getContent(false);
            $this->logger->error('Dropbox API error: ' . $errorContent);
            return [
                'path' => $cloudPath,
                'id' => null,
                'isSimulated' => true,
                'message' => 'Dropbox API responded with HTTP ' . $statusCode . '. JSON saved locally for Power Automate inspection.',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Dropbox API exception: ' . $e->getMessage());
            return [
                'path' => $cloudPath,
                'id' => null,
                'isSimulated' => true,
                'message' => 'Dropbox upload error (' . $e->getMessage() . '). JSON ticket saved locally.',
            ];
        }
    }

    /**
     * Save ticket JSON to var/support_tickets directory.
     */
    private function saveLocalCopy(string $fileName, string $jsonContent): string
    {
        $filePath = $this->storageDir . '/' . $fileName;
        file_put_contents($filePath, $jsonContent);
        return $filePath;
    }

    /**
     * Retrieve list of generated tickets.
     */
    public function listGeneratedTickets(): array
    {
        if (!is_dir($this->storageDir)) {
            return [];
        }

        $files = scandir($this->storageDir, SCANDIR_SORT_DESCENDING);
        $tickets = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.json')) {
                continue;
            }

            $fullPath = $this->storageDir . '/' . $file;
            $content = @file_get_contents($fullPath);
            $json = $content ? json_decode($content, true) : null;

            if ($json) {
                $tickets[] = [
                    'fileName' => $file,
                    'size' => filesize($fullPath),
                    'timestamp' => filemtime($fullPath),
                    'data' => $json,
                ];
            }
        }

        return $tickets;
    }
}

