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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @method User|null getUser()
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users/create', name: 'app_admin_user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $firstName = trim($data['firstName'] ?? '');
        $lastName = trim($data['lastName'] ?? '');
        $roleSlug = $data['roleSlug'] ?? 'ROLE_CANDIDATE';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Valid email address is required.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (!$password || strlen($password) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters.');
            return $this->redirectToRoute('app_admin_users');
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'An account with this email already exists.');
            return $this->redirectToRoute('app_admin_users');
        }

        $role = $em->getRepository(Role::class)->findOneBy(['slug' => $roleSlug]);
        if (!$role) {
            $this->addFlash('error', 'Invalid role selected.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRole($role);
        $user->setIsVerified(true);

        $details = new \App\Entity\UserDetails();
        $details->setUser($user);
        $details->setFirstName($firstName ?: explode('@', $email)[0]);
        $details->setLastName($lastName);
        $em->persist($details);

        if ($roleSlug === 'ROLE_CANDIDATE') {
            $candidateProfile = new \App\Entity\CandidateProfile();
            $candidateProfile->setUser($user);
            $em->persist($candidateProfile);
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', "User {$email} created successfully as {$role->getName()}.");
        return $this->redirectToRoute('app_admin_users');
    }
    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request, InertiaService $inertia, EntityManagerInterface $em): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $search = trim($request->query->get('search', ''));
        $sort = $request->query->get('sort', 'id');
        $dir = strtolower($request->query->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $qb = $em->getRepository(User::class)->createQueryBuilder('u')
            ->leftJoin('u.userDetails', 'ud')
            ->leftJoin('u.role', 'r');

        if ($search !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :search OR LOWER(ud.firstName) LIKE :search OR LOWER(ud.lastName) LIKE :search OR LOWER(r.name) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $allUsers = $qb->getQuery()->getResult();
        $totalRows = count($allUsers);

        // Sort
        $allowedSorts = ['fullName', 'email', 'role', 'isBlocked', 'isVerified', 'id'];
        $sortField = in_array($sort, $allowedSorts) ? $sort : 'id';

        usort($allUsers, function (User $a, User $b) use ($sortField, $dir) {
            $nameA = $a->getUserDetails() ? ($a->getUserDetails()->getFirstName() . ' ' . $a->getUserDetails()->getLastName()) : 'User';
            $nameB = $b->getUserDetails() ? ($b->getUserDetails()->getFirstName() . ' ' . $b->getUserDetails()->getLastName()) : 'User';
            $valA = match ($sortField) {
                'fullName' => $nameA,
                'email' => $a->getEmail(),
                'role' => $a->getRole()?->getName() ?? '',
                'isBlocked' => $a->isBlocked() ? 1 : 0,
                'isVerified' => $a->isVerified() ? 1 : 0,
                default => $a->getId(),
            };
            $valB = match ($sortField) {
                'fullName' => $nameB,
                'email' => $b->getEmail(),
                'role' => $b->getRole()?->getName() ?? '',
                'isBlocked' => $b->isBlocked() ? 1 : 0,
                'isVerified' => $b->isVerified() ? 1 : 0,
                default => $b->getId(),
            };
            $cmp = is_string($valA) ? strcasecmp((string)$valA, (string)$valB) : ($valA <=> $valB);
            return $dir === 'asc' ? $cmp : -$cmp;
        });

        $offset = ($page - 1) * $limit;
        $slicedUsers = array_slice($allUsers, $offset, $limit);

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
                'isSelf' => $this->getUser()?->getId() === $u->getId(),
            ];
        }, $slicedUsers);

        $roles = $em->getRepository(Role::class)->findAll();
        $roleData = array_map(fn(Role $r) => [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'slug' => $r->getSlug(),
        ], $roles);

        return $inertia->render('admin/Users', [
            'users' => $userData,
            'roles' => $roleData,
            'totalRows' => $totalRows,
            'currentPage' => $page,
            'pageSize' => $limit,
            'search' => $search,
            'sort' => $sortField,
            'sortDir' => $dir,
        ]);
    }

    #[Route('/users/{id}/toggle-block', name: 'app_admin_user_toggle_block', methods: ['POST'])]
    public function toggleBlock(User $user, EntityManagerInterface $em): Response
    {
        if ($this->getUser()?->getId() === $user->getId()) {
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
        if ($this->getUser()?->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot delete your own account while logged in.');
            return $this->redirectToRoute('app_admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('app_admin_users');
    }
}
