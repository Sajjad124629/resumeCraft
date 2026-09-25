<?php

namespace App\Controller;

use App\Entity\Attribute;
use App\Entity\CandidateAttributeValue;
use App\Entity\CandidateProfile;
use App\Entity\Project;
use App\Entity\User;
use App\Service\AchievementService;
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
#[Route('/profile')]
class ProfileController extends AbstractController
{
    private function resolveCandidate(EntityManagerInterface $em, ?int $candidateId = null): CandidateProfile
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Login required.');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if ($candidateId !== null && $isAdmin) {
            $candidate = $em->getRepository(CandidateProfile::class)->find($candidateId);
            if (!$candidate) {
                throw $this->createNotFoundException('Candidate profile not found.');
            }
            return $candidate;
        }

        $candidate = $user->getCandidateProfile();
        if (!$candidate) {
            throw $this->createAccessDeniedException('No candidate profile associated with your user.');
        }

        return $candidate;
    }

    #[Route('', name: 'app_profile_index', methods: ['GET'])]
    public function index(InertiaService $inertia, EntityManagerInterface $em, AchievementService $achievementService): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $candidate = $user->getCandidateProfile();
        if (!$candidate) {
            return $this->redirectToRoute('app_profile_settings');
        }

        return $this->renderProfileView($candidate, $inertia, $em, $achievementService, false);
    }

    #[Route('/settings', name: 'app_profile_settings', methods: ['GET'])]
    public function settings(InertiaService $inertia, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Account settings is strictly for Admin and Recruiter. Candidates use /profile
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_RECRUITER', $roles)) {
            return $this->redirectToRoute('app_profile_index');
        }

        /** @var User $freshUser */
        $freshUser = $em->find(User::class, $user->getId()) ?? $user;
        try {
            $em->refresh($freshUser);
        } catch (\Exception $e) {
        }
        $details = $freshUser->getUserDetails() ?? $em->getRepository(\App\Entity\UserDetails::class)->findOneBy(['user' => $freshUser]);
        if ($details) {
            try {
                $em->refresh($details);
            } catch (\Exception $e) {
            }
        }
        $candidate = $freshUser->getCandidateProfile();

        return $inertia->render('profile/Settings', [
            'user' => [
                'id' => $freshUser->getId(),
                'email' => $freshUser->getEmail(),
                'role' => $freshUser->getRole()?->getSlug() ?? 'ROLE_USER',
                'roleName' => $freshUser->getRole()?->getName() ?? 'User',
                'isVerified' => $freshUser->isVerified(),
                'hasPassword' => !empty($freshUser->getPassword()),
            ],
            'profile' => [
                'firstName' => $details?->getFirstName() ?? '',
                'lastName' => $details?->getLastName() ?? '',
                'phone' => $details?->getPhone() ?? '',
                'photo' => $details?->getPhoto() ?? '',
                'location' => $candidate?->getLocation() ?? '',
            ],
        ]);
    }

    #[Route('/settings/update', name: 'app_profile_settings_update', methods: ['POST', 'PUT'])]
    public function updateSettings(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        /** @var User $freshUser */
        $freshUser = $em->find(User::class, $user->getId()) ?? $user;
        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());

        $details = $freshUser->getUserDetails() ?? $em->getRepository(\App\Entity\UserDetails::class)->findOneBy(['user' => $freshUser]);
        if (!$details) {
            $details = new \App\Entity\UserDetails();
            $details->setUser($freshUser);
            $freshUser->setUserDetails($details);
            $em->persist($details);
        }

        if (isset($data['firstName'])) $details->setFirstName(trim($data['firstName']));
        if (isset($data['lastName'])) $details->setLastName(trim($data['lastName']));
        if (array_key_exists('phone', $data)) $details->setPhone($data['phone'] ? trim($data['phone']) : null);
        if (array_key_exists('photo', $data)) $details->setPhoto($data['photo']);

        $freshUser->setUserDetails($details);

        if (array_key_exists('location', $data)) {
            $candidate = $freshUser->getCandidateProfile();
            if ($candidate) {
                $candidate->setLocation($data['location'] ? trim($data['location']) : null);
            }
        }

        $em->flush();

        $fullName = trim(($details->getFirstName() ?? '') . ' ' . ($details->getLastName() ?? '')) ?: 'User';

        if ($request->headers->get('X-Inertia')) {
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile_settings');
        }

        return $this->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user' => [
                'fullName' => $fullName,
                'name' => $fullName,
                'photo' => $details->getPhoto(),
            ],
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? ($data['newPasswordConfirmation'] ?? '');

        // If user already has a password, verify current password
        if (!empty($user->getPassword())) {
            if (!$currentPassword) {
                return $this->json(['error' => 'Current password is required.'], 422);
            }
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['error' => 'The current password you entered is incorrect.'], 422);
            }
        }

        if (!$newPassword || strlen($newPassword) < 6) {
            return $this->json(['error' => 'New password must be at least 6 characters long.'], 422);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->json(['error' => 'New password and confirmation password do not match.'], 422);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Your password has been changed successfully!',
        ]);
    }

    #[Route('/candidate/{id}', name: 'app_profile_candidate_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function candidateProfile(int $id, InertiaService $inertia, EntityManagerInterface $em, AchievementService $achievementService): Response
    {
        $candidate = $this->resolveCandidate($em, $id);
        return $this->renderProfileView($candidate, $inertia, $em, $achievementService, true);
    }

    #[Route('/public/{id}', name: 'app_profile_public', methods: ['GET'])]
    public function publicProfile(int $id, InertiaService $inertia, EntityManagerInterface $em, AchievementService $achievementService): Response
    {
        $candidate = $em->getRepository(CandidateProfile::class)->find($id);
        if (!$candidate) {
            throw $this->createNotFoundException('Candidate not found.');
        }

        $attributes = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $attributesData = array_map(fn($v) => [
            'name' => $v->getAttribute()->getName(),
            'type' => $v->getAttribute()->getType(),
            'value' => $v->getValue(),
        ], $attributes);

        $projects = $candidate->getProjects();
        $projectsData = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'tags' => $p->getTags() ?? [],
            'periodStart' => $p->getDateStart() ? $p->getDateStart()->format('Y-m-d') : null,
            'periodEnd' => $p->getDateEnd() ? $p->getDateEnd()->format('Y-m-d') : null,
        ], $projects->toArray());

        $achievements = $achievementService->getAchievements($candidate);

        return $inertia->render('profile/PublicView', [
            'candidate' => [
                'id' => $candidate->getId(),
                'firstName' => $candidate->getUser()->getUserDetails()?->getFirstName(),
                'lastName' => $candidate->getUser()->getUserDetails()?->getLastName(),
                'location' => $candidate->getLocation(),
                'photo' => $candidate->getUser()->getUserDetails()?->getPhoto(),
            ],
            'attributes' => $attributesData,
            'projects' => $projectsData,
            'achievements' => $achievements,
        ]);
    }

    private function renderProfileView(CandidateProfile $candidate, InertiaService $inertia, EntityManagerInterface $em, AchievementService $achievementService, bool $isAdminEditing): Response
    {
        $achievements = $achievementService->getAchievements($candidate);

        $projectsData = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'tags' => $p->getTags() ?? [],
            'periodStart' => $p->getDateStart() ? $p->getDateStart()->format('Y-m-d') : null,
            'periodEnd' => $p->getDateEnd() ? $p->getDateEnd()->format('Y-m-d') : null,
        ], $candidate->getProjects()->toArray());

        $candidateAttributes = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $candidateAttributesData = array_map(fn($v) => [
            'attributeId' => $v->getAttribute()->getId(),
            'name' => $v->getAttribute()->getName(),
            'type' => $v->getAttribute()->getType(),
            'options' => $v->getAttribute()->getOptions(),
            'value' => $v->getValue(),
            'valVersion' => $v->getVersion(),
        ], $candidateAttributes);

        $allAttributes = $em->getRepository(Attribute::class)->findAll();
        $allAttributesData = array_map(fn($a) => [
            'id' => $a->getId(),
            'name' => $a->getName(),
            'type' => $a->getType(),
            'options' => $a->getOptions(),
            'category' => [
                'id' => $a->getCategory()?->getId(),
                'name' => $a->getCategory()?->getName(),
            ]
        ], $allAttributes);

        // Section 4: CVs with access rules check
        $candidateAttrMap = [];
        foreach ($candidateAttributes as $v) {
            $candidateAttrMap[$v->getAttribute()->getId()] = $v->getValue();
        }

        $allCvs = $candidate->getCvs();
        $cvsData = [];
        foreach ($allCvs as $c) {
            $pos = $c->getPosition();
            $canAccess = true;
            if (!$pos->isPublic()) {
                foreach ($pos->getAccessRules() as $rule) {
                    $attrId = $rule->getAttribute()->getId();
                    $candValue = $candidateAttrMap[$attrId] ?? null;
                    $ruleValue = $rule->getValue();
                    $op = $rule->getOperator();

                    if ($candValue === null) {
                        $canAccess = false;
                        break;
                    }
                    if ($op === '=') {
                        if ((string)$candValue !== (string)$ruleValue) $canAccess = false;
                    } elseif ($op === '>') {
                        if ((float)$candValue <= (float)$ruleValue) $canAccess = false;
                    } elseif ($op === '<') {
                        if ((float)$candValue >= (float)$ruleValue) $canAccess = false;
                    }
                    if (!$canAccess) break;
                }
            }

            // Hidden from candidate UI if access lost
            if (!$canAccess && !$isAdminEditing) {
                continue;
            }

            $cvsData[] = [
                'id' => $c->getId(),
                'positionId' => $pos->getId(),
                'positionTitle' => $pos->getTitle(),
                'company' => $pos->getCompany(),
                'level' => $pos->getLevel(),
                'status' => $c->getStatus(),
                'likes' => $c->getLikes()->count(),
                'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }

        return $inertia->render('profile/Index', [
            'profile' => [
                'id' => $candidate->getId(),
                'firstName' => $candidate->getUser()->getUserDetails()?->getFirstName(),
                'lastName' => $candidate->getUser()->getUserDetails()?->getLastName(),
                'location' => $candidate->getLocation(),
                'photo' => $candidate->getUser()->getUserDetails()?->getPhoto(),
                'version' => $candidate->getVersion(),
            ],
            'projects' => $projectsData,
            'candidateAttributes' => $candidateAttributesData,
            'availableAttributes' => $allAttributesData,
            'achievements' => $achievements,
            'cvs' => $cvsData,
            'isAdminEditing' => $isAdminEditing,
            'targetCandidateId' => $isAdminEditing ? $candidate->getId() : null,
        ]);
    }

    #[Route('/attributes/update', name: 'app_profile_update_attr', methods: ['POST', 'PUT'])]
    public function updateAttribute(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $candidateId = isset($data['candidateId']) ? (int)$data['candidateId'] : null;
        $candidate = $this->resolveCandidate($em, $candidateId);

        $attrId = $data['attributeId'] ?? null;
        $value = $data['value'] ?? null;
        $version = $data['version'] ?? 1;

        if (!$attrId) {
            return $this->json(['error' => 'Missing attributeId'], 400);
        }

        $attribute = $em->getRepository(Attribute::class)->find($attrId);
        if (!$attribute) {
            return $this->json(['error' => 'Attribute not found'], 404);
        }

        $valEntity = $em->getRepository(CandidateAttributeValue::class)->findOneBy([
            'candidate' => $candidate,
            'attribute' => $attribute
        ]);

        if (!$valEntity) {
            $valEntity = new CandidateAttributeValue();
            $valEntity->setCandidate($candidate);
            $valEntity->setAttribute($attribute);
            $em->persist($valEntity);
        } else {
            // Optimistic locking
            if ($valEntity->getVersion() !== (int)$version) {
                return $this->json(['error' => 'Conflict: Attribute modified elsewhere'], 409);
            }
        }

        $valEntity->setValue($value);
        try {
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return $this->json(['error' => 'Conflict: Attribute modified elsewhere'], 409);
        }

        return $this->json([
            'success' => true,
            'valVersion' => $valEntity->getVersion()
        ]);
    }

    #[Route('/attributes/batch-delete', name: 'app_profile_batch_delete_attr', methods: ['POST', 'DELETE'])]
    public function batchDeleteAttributes(Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());
        $candidateId = isset($data['candidateId']) ? (int)$data['candidateId'] : null;
        $candidate = $this->resolveCandidate($em, $candidateId);
        $ids = $data['ids'] ?? [];

        $count = 0;
        if (is_array($ids)) {
            foreach ($ids as $attrId) {
                $attribute = $em->getRepository(Attribute::class)->find((int)$attrId);
                if ($attribute) {
                    $valEntity = $em->getRepository(CandidateAttributeValue::class)->findOneBy([
                        'candidate' => $candidate,
                        'attribute' => $attribute
                    ]);
                    if ($valEntity) {
                        $em->remove($valEntity);
                        $count++;
                    }
                }
            }
            $em->flush();
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $this->addFlash('success', sprintf('%d attribute(s) removed successfully.', $count));
        $redirectRoute = $isAdmin && $candidateId && (int)$candidateId !== $user->getCandidateProfile()?->getId()
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');
        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }

    #[Route('/attributes/{attributeId}', name: 'app_profile_delete_attr', methods: ['DELETE', 'POST'], requirements: ['attributeId' => '\d+'])]
    public function deleteAttribute(int $attributeId, Request $request, EntityManagerInterface $em): Response
    {
        $candidateId = $request->query->get('candidateId') ? (int)$request->query->get('candidateId') : null;
        $candidate = $this->resolveCandidate($em, $candidateId);
        $attribute = $em->getRepository(Attribute::class)->find($attributeId);

        if ($attribute) {
            $valEntity = $em->getRepository(CandidateAttributeValue::class)->findOneBy([
                'candidate' => $candidate,
                'attribute' => $attribute
            ]);

            if ($valEntity) {
                $em->remove($valEntity);
                $em->flush();
                $this->addFlash('success', 'Attribute removed successfully.');
            }
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $redirectRoute = $isAdmin && $candidateId && (int)$candidateId !== $user->getCandidateProfile()?->getId()
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');
        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }

    #[Route('/update-me', name: 'app_profile_update_me', methods: ['POST', 'PUT'])]
    public function updateMe(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $candidateId = isset($data['candidateId']) ? (int)$data['candidateId'] : null;
        $candidate = $this->resolveCandidate($em, $candidateId);

        $version = $data['version'] ?? 1;

        if ($candidate->getVersion() !== (int)$version) {
            return $this->json(['error' => 'Conflict: Profile modified elsewhere'], 409);
        }

        $user = $candidate->getUser();
        $details = $user->getUserDetails() ?? $em->getRepository(\App\Entity\UserDetails::class)->findOneBy(['user' => $user]);
        if (!$details) {
            $details = new \App\Entity\UserDetails();
            $details->setUser($user);
            $user->setUserDetails($details);
            $em->persist($details);
        }

        if (isset($data['firstName'])) $details->setFirstName(trim($data['firstName']));
        if (isset($data['lastName'])) $details->setLastName(trim($data['lastName']));
        if (isset($data['location'])) $candidate->setLocation($data['location']);
        if (array_key_exists('photo', $data)) {
            $details->setPhoto($data['photo']);
        }
        $user->setUserDetails($details);

        try {
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return $this->json(['error' => 'Conflict: Profile modified elsewhere'], 409);
        }

        $fullName = trim(($details->getFirstName() ?? '') . ' ' . ($details->getLastName() ?? '')) ?: 'User';

        return $this->json([
            'success' => true,
            'version' => $candidate->getVersion(),
            'fullName' => $fullName,
            'photo' => $details->getPhoto(),
        ]);
    }

    #[Route('/projects/create', name: 'app_profile_project_create_view', methods: ['GET'])]
    public function createProjectView(Request $request, InertiaService $inertia, EntityManagerInterface $em): Response
    {
        $candidateId = $request->query->get('candidateId') ? (int)$request->query->get('candidateId') : null;
        $candidate = $this->resolveCandidate($em, $candidateId);
        $existingTags = [];
        if ($candidate) {
            foreach ($candidate->getProjects() as $p) {
                foreach ($p->getTags() ?? [] as $t) {
                    $existingTags[] = $t;
                }
            }
        }
        $existingTags = array_values(array_unique($existingTags));

        return $inertia->render('profile/projects/Create', [
            'existingTags' => $existingTags,
            'candidateId' => $candidateId,
        ]);
    }

    #[Route('/projects', name: 'app_profile_project_create', methods: ['POST'])]
    public function createProject(Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());
        $candidateId = isset($data['candidateId']) ? (int)$data['candidateId'] : null;
        $candidate = $this->resolveCandidate($em, $candidateId);

        $project = new Project();
        $project->setCandidate($candidate);
        $project->setName($data['name'] ?? '');
        $project->setDescription($data['description'] ?? '');
        $project->setDateStart(new \DateTime($data['periodStart'] ?? 'now'));
        if (!empty($data['periodEnd'])) {
            $project->setDateEnd(new \DateTime($data['periodEnd']));
        }
        if (isset($data['tags']) && is_array($data['tags'])) {
            $project->setTags($data['tags']);
        }

        $em->persist($project);
        $em->flush();
        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $redirectRoute = $isAdmin && $candidateId && (int)$candidateId !== $user->getCandidateProfile()?->getId()
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');
        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }

    #[Route('/projects/{id}/edit', name: 'app_profile_project_edit_view', methods: ['GET'])]
    public function editProjectView(Project $project, InertiaService $inertia): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if (!$isAdmin && $project->getCandidate() !== $user->getCandidateProfile()) {
            throw $this->createAccessDeniedException();
        }

        $existingTags = [];
        if ($project->getCandidate()) {
            foreach ($project->getCandidate()->getProjects() as $p) {
                foreach ($p->getTags() ?? [] as $t) {
                    $existingTags[] = $t;
                }
            }
        }
        $existingTags = array_values(array_unique($existingTags));

        return $inertia->render('profile/projects/Edit', [
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'tags' => $project->getTags() ?? [],
                'periodStart' => $project->getDateStart() ? $project->getDateStart()->format('Y-m-d') : null,
                'periodEnd' => $project->getDateEnd() ? $project->getDateEnd()->format('Y-m-d') : null,
            ],
            'existingTags' => $existingTags,
            'candidateId' => $isAdmin ? $project->getCandidate()->getId() : null,
        ]);
    }

    #[Route('/projects/batch-delete', name: 'app_profile_project_batch_delete', methods: ['POST', 'DELETE'])]
    public function batchDeleteProjects(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());
        $ids = $data['ids'] ?? [];
        $candidateId = $data['candidateId'] ?? null;

        $count = 0;
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $project = $em->getRepository(Project::class)->find((int)$id);
                if ($project) {
                    if ($isAdmin || $project->getCandidate() === $user->getCandidateProfile()) {
                        $em->remove($project);
                        $count++;
                    }
                }
            }
            $em->flush();
        }

        $this->addFlash('success', sprintf('%d project(s) deleted successfully.', $count));

        $redirectRoute = $isAdmin && $candidateId && (int)$candidateId !== $user->getCandidateProfile()?->getId()
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');

        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }

    #[Route('/projects/{id}', name: 'app_profile_project_edit', methods: ['POST', 'PUT'], requirements: ['id' => '\d+'])]
    public function editProject(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if (!$isAdmin && $project->getCandidate() !== $user->getCandidateProfile()) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());

        if (isset($data['name'])) $project->setName($data['name']);
        if (isset($data['description'])) $project->setDescription($data['description']);
        if (!empty($data['periodStart'])) {
            $project->setDateStart(new \DateTime($data['periodStart']));
        }
        if (array_key_exists('periodEnd', $data)) {
            $project->setDateEnd(!empty($data['periodEnd']) ? new \DateTime($data['periodEnd']) : null);
        }
        if (isset($data['tags']) && is_array($data['tags'])) {
            $project->setTags($data['tags']);
        }

        $em->flush();
        $this->addFlash('success', 'Project updated successfully.');

        $candidateId = $project->getCandidate()->getId();
        $redirectRoute = $isAdmin && $project->getCandidate() !== $user->getCandidateProfile()
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');

        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }

    #[Route('/projects/{id}', name: 'app_profile_project_delete', methods: ['DELETE', 'POST'], requirements: ['id' => '\d+'])]
    public function deleteProject(Project $project, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if (!$isAdmin && $project->getCandidate() !== $user->getCandidateProfile()) {
            throw $this->createAccessDeniedException();
        }

        $candidateId = $project->getCandidate()->getId();
        $isOtherCandidate = $project->getCandidate() !== $user->getCandidateProfile();

        $em->remove($project);
        $em->flush();
        $this->addFlash('success', 'Project deleted successfully.');

        $redirectRoute = $isAdmin && $isOtherCandidate
            ? $this->generateUrl('app_profile_candidate_admin', ['id' => $candidateId])
            : $this->generateUrl('app_profile_index');

        return $this->redirect($redirectRoute, Response::HTTP_SEE_OTHER);
    }
}
