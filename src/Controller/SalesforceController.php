<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SalesforceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/salesforce')]
class SalesforceController extends AbstractController
{
    #[Route('/sync', name: 'app_salesforce_sync', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function sync(
        Request $request,
        SalesforceService $salesforceService,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());

        // Determine target user (self or target if admin)
        $targetUserId = isset($data['userId']) ? (int)$data['userId'] : $currentUser->getId();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());

        if ($targetUserId !== $currentUser->getId() && !$isAdmin) {
            return $this->json(['success' => false, 'error' => 'Permission denied. Only admins can sync other users.'], 403);
        }

        $targetUser = ($targetUserId === $currentUser->getId())
            ? $currentUser
            : $em->getRepository(User::class)->find($targetUserId);

        if (!$targetUser) {
            return $this->json(['success' => false, 'error' => 'Target user not found.'], 404);
        }

        try {
            $result = $salesforceService->syncUserToSalesforce($targetUser, $data);

            return $this->json([
                'success' => true,
                'message' => 'Successfully synced with Salesforce CRM! Created Account and linked Contact.',
                'accountId' => $result['accountId'],
                'contactId' => $result['contactId'],
                'instanceUrl' => $result['instanceUrl'],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
