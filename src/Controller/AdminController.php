<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @method User|null getUser()
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(InertiaService $inertia, EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        $roles = $em->getRepository(Role::class)->findAll();

        $userData = array_map(function (User $u) {
            return [
                'id' => $u->getId(),
                'email' => $u->getEmail(),
                'role' => [
                    'id' => $u->getRole()?->getId(),
                    'name' => $u->getRole()?->getName() ?? 'User',
                    'slug' => $u->getRole()?->getSlug() ?? 'ROLE_USER',
                ],
                'isBlocked' => $u->isBlocked(),
                'isVerified' => $u->isVerified(),
                'candidateProfileId' => $u->getCandidateProfile()?->getId(),
                'fullName' => $u->getUserDetails() ? ($u->getUserDetails()->getFirstName() . ' ' . $u->getUserDetails()->getLastName()) : 'User',
                'location' => $u->getCandidateProfile()?->getLocation() ?? 'N/A',
                'isSelf' => $this->getUser() === $u,
            ];
        }, $users);

        $roleData = array_map(fn(Role $r) => [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'slug' => $r->getSlug(),
        ], $roles);

        return $inertia->render('admin/Users', [
            'users' => $userData,
            'roles' => $roleData,
        ]);
    }

    #[Route('/users/{id}/toggle-block', name: 'app_admin_user_toggle_block', methods: ['POST'])]
    public function toggleBlock(User $user, EntityManagerInterface $em): Response
    {
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'You cannot block your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsBlocked(!$user->isBlocked());
        $em->flush();

        $status = $user->isBlocked() ? 'blocked' : 'unblocked';
        $this->addFlash('success', "User {$user->getEmail()} has been {$status}.");
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/role', name: 'app_admin_user_change_role', methods: ['POST', 'PUT'])]
    public function changeRole(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $roleSlug = $data['roleSlug'] ?? null;

        if (!$roleSlug) {
            return $this->redirectToRoute('app_admin_users');
        }

        $role = $em->getRepository(Role::class)->findOneBy(['slug' => $roleSlug]);
        if (!$role) {
            $this->addFlash('error', 'Invalid role selected.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setRole($role);
        if ($roleSlug === 'ROLE_CANDIDATE' && !$user->getCandidateProfile()) {
            $profile = new \App\Entity\CandidateProfile();
            $profile->setUser($user);
            $em->persist($profile);
        }
        if (!$user->getUserDetails()) {
            $details = new \App\Entity\UserDetails();
            $details->setUser($user);
            $details->setFirstName(explode('@', $user->getEmail())[0]);
            $details->setLastName('');
            $em->persist($details);
        }
        $em->flush();

        $this->addFlash('success', "Role for {$user->getEmail()} changed to {$role->getName()}.");
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}', name: 'app_admin_user_delete', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'You cannot delete your own account while logged in.');
            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('app_admin_users');
    }
}
