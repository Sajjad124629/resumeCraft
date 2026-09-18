<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\Attribute;
use App\Entity\CandidateAttributeValue;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Entity\User;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @method User|null getUser()
 */
#[Route('/positions')]
class PositionController extends AbstractController
{
    #[Route('', name: 'app_position_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $search = trim($request->query->get('search', ''));
        $sort = $request->query->get('sort', 'id');
        $dir = strtolower($request->query->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        /** @var User|null $user */
        $user = $this->getUser();
        $isCandidate = $user && in_array('ROLE_CANDIDATE', $user->getRoles());
        $isRecruiter = $user && in_array('ROLE_RECRUITER', $user->getRoles());
        $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());

        $candidateAttrMap = [];
        if ($isCandidate && $user->getCandidateProfile()) {
            $candidate = $user->getCandidateProfile();
            $vals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
            foreach ($vals as $v) {
                $candidateAttrMap[$v->getAttribute()->getId()] = $v->getValue();
            }
        }

        $qb = $em->getRepository(Position::class)->createQueryBuilder('p');

        if (!$user) {
            $qb->andWhere('p.isPublic = true');
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(p.title) LIKE :search OR LOWER(p.company) LIKE :search OR LOWER(p.level) LIKE :search OR LOWER(p.shortDescription) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $allPositions = $qb->getQuery()->getResult();

        $matchingPositions = [];
        foreach ($allPositions as $p) {
            if ($isCandidate && !$p->isPublic()) {
                $canAccess = true;
                foreach ($p->getAccessRules() as $rule) {
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
                if (!$canAccess) continue;
            }
            $matchingPositions[] = $p;
        }

        $totalRows = count($matchingPositions);

        // Sort
        $allowedSorts = ['title', 'company', 'level', 'isPublic', 'id'];
        $sortField = in_array($sort, $allowedSorts) ? $sort : 'id';

        usort($matchingPositions, function (Position $a, Position $b) use ($sortField, $dir) {
            $valA = match ($sortField) {
                'title' => $a->getTitle(),
                'company' => $a->getCompany() ?? '',
                'level' => $a->getLevel() ?? '',
                'isPublic' => $a->isPublic() ? 1 : 0,
                default => $a->getId(),
            };
            $valB = match ($sortField) {
                'title' => $b->getTitle(),
                'company' => $b->getCompany() ?? '',
                'level' => $b->getLevel() ?? '',
                'isPublic' => $b->isPublic() ? 1 : 0,
                default => $b->getId(),
            };
            $cmp = is_string($valA) ? strcasecmp((string)$valA, (string)$valB) : ($valA <=> $valB);
            return $dir === 'asc' ? $cmp : -$cmp;
        });

        $offset = ($page - 1) * $limit;
        $slicedPositions = array_slice($matchingPositions, $offset, $limit);

        $positionArray = array_map(fn(Position $p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'shortDescription' => $p->getShortDescription(),
            'isPublic' => $p->isPublic(),
            'level' => $p->getLevel(),
            'company' => $p->getCompany(),
            'cvCount' => $p->getCvs()->count(),
            'version' => $p->getVersion(),
            'projectTags' => $p->getProjectTags() ?? [],
            'attributes' => array_map(fn($a) => $a->getId(), $p->getAttributes()->toArray()),
            'accessRules' => array_map(fn($r) => [
                'attributeId' => $r->getAttribute()->getId(),
                'operator' => $r->getOperator(),
                'value' => $r->getValue(),
            ], $p->getAccessRules()->toArray()),
        ], $slicedPositions);

        $attributes = $em->getRepository(Attribute::class)->findAll();
        $attributeArray = array_map(fn($a) => ['id' => $a->getId(), 'name' => $a->getName(), 'type' => $a->getType()], $attributes);

        return $inertia->render('positions/Index', [
            'positions' => $positionArray,
            'totalRows' => $totalRows,
            'currentPage' => $page,
            'pageSize' => $limit,
            'search' => $search,
            'sort' => $sort,
            'sortDir' => $dir,
            'availableAttributes' => $attributeArray,
        ]);
    }

    #[Route('/create', name: 'app_position_create_view', methods: ['GET'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function createView(EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $attributes = $em->getRepository(Attribute::class)->findAll();
        $attributeArray = array_map(fn($a) => ['id' => $a->getId(), 'name' => $a->getName(), 'type' => $a->getType()], $attributes);

        return $inertia->render('positions/Create', [
            'availableAttributes' => $attributeArray,
        ]);
    }

    #[Route('/create', name: 'app_position_create', methods: ['POST'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $position = new Position();
        $position->setTitle($data['title']);
        $position->setShortDescription($data['shortDescription'] ?? '');
        $position->setIsPublic($data['isPublic'] ?? true);
        $position->setLevel($data['level'] ?? null);
        $position->setCompany($data['company'] ?? null);
        $position->setMaxProjects($data['maxProjects'] ?? 3);
        if (isset($data['projectTags']) && is_array($data['projectTags'])) {
            $position->setProjectTags($data['projectTags']);
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $attrId) {
                $attr = $em->getRepository(Attribute::class)->find($attrId);
                if ($attr) {
                    $position->addAttribute($attr);
                }
            }
        }

        if (isset($data['accessRules']) && is_array($data['accessRules'])) {
            foreach ($data['accessRules'] as $ruleData) {
                $attr = $em->getRepository(Attribute::class)->find($ruleData['attributeId']);
                if ($attr) {
                    $rule = new PositionAccessRule();
                    $rule->setPosition($position);
                    $rule->setAttribute($attr);
                    $rule->setOperator($ruleData['operator']);
                    $rule->setValue($ruleData['value']);
                    $em->persist($rule);
                }
            }
        }

        $em->persist($position);
        $em->flush();
        $this->addFlash('success', 'Position created successfully.');
        return $this->redirectToRoute('app_position_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_position_show', methods: ['GET'])]
    public function show(Position $position, InertiaService $inertia): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $isRecruiter = $user && (in_array('ROLE_RECRUITER', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()));

        $attributes = array_map(fn(Attribute $a) => [
            'id' => $a->getId(),
            'name' => $a->getName(),
            'type' => $a->getType(),
        ], $position->getAttributes()->toArray());

        $cvsData = [];
        if ($isRecruiter) {
            $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
            $allPositionCvs = $position->getCvs()->toArray();

            // Recruiters can view only published CVs (Admins can view all)
            $visibleCvs = array_filter($allPositionCvs, function (\App\Entity\Cv $c) use ($isAdmin) {
                if ($isAdmin) return true;
                return $c->getStatus() === 'published';
            });

            $cvsData = array_values(array_map(fn(\App\Entity\Cv $c) => [
                'id' => $c->getId(),
                'candidateName' => $c->getCandidate()->getUser()->getUserDetails() ? ($c->getCandidate()->getUser()->getUserDetails()->getFirstName() . ' ' . $c->getCandidate()->getUser()->getUserDetails()->getLastName()) : 'User',
                'candidateProfileId' => $c->getCandidate()->getId(),
                'status' => $c->getStatus(),
                'likes' => $c->getLikes()->count(),
                'createdAt' => $c->getCreatedAt()->format('Y-m-d H:i'),
            ], $visibleCvs));
        }

        return $inertia->render('positions/Show', [
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'shortDescription' => $position->getShortDescription(),
                'isPublic' => $position->isPublic(),
                'level' => $position->getLevel(),
                'company' => $position->getCompany(),
                'maxProjects' => $position->getMaxProjects(),
                'projectTags' => $position->getProjectTags() ?? [],
                'attributes' => $attributes,
                'version' => $position->getVersion(),
                'cvs' => $cvsData,
                'accessRules' => array_map(fn($r) => [
                    'attributeId' => $r->getAttribute()->getId(),
                    'operator' => $r->getOperator(),
                    'value' => $r->getValue(),
                ], $position->getAccessRules()->toArray()),
            ],
        ]);
    }

    #[Route('/{id}/duplicate', name: 'app_position_duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function duplicate(Position $position, EntityManagerInterface $em): Response
    {
        $newPos = new Position();
        $newPos->setTitle($position->getTitle() . ' (Copy)');
        $newPos->setShortDescription($position->getShortDescription());
        $newPos->setIsPublic($position->isPublic());
        $newPos->setLevel($position->getLevel());
        $newPos->setCompany($position->getCompany());
        $newPos->setMaxProjects($position->getMaxProjects());
        $newPos->setProjectTags($position->getProjectTags());

        foreach ($position->getAttributes() as $attr) {
            $newPos->addAttribute($attr);
        }

        foreach ($position->getAccessRules() as $rule) {
            $newRule = new PositionAccessRule();
            $newRule->setPosition($newPos);
            $newRule->setAttribute($rule->getAttribute());
            $newRule->setOperator($rule->getOperator());
            $newRule->setValue($rule->getValue());
            $em->persist($newRule);
        }

        $em->persist($newPos);
        $em->flush();

        $this->addFlash('success', 'Position duplicated successfully.');
        return $this->redirectToRoute('app_position_index');
    }

    #[Route('/{id}/edit', name: 'app_position_edit_view', methods: ['GET'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function editView(Position $position, EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $attributes = $em->getRepository(Attribute::class)->findAll();
        $attributeArray = array_map(fn($a) => ['id' => $a->getId(), 'name' => $a->getName(), 'type' => $a->getType()], $attributes);

        return $inertia->render('positions/Edit', [
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'shortDescription' => $position->getShortDescription(),
                'isPublic' => $position->isPublic(),
                'level' => $position->getLevel(),
                'company' => $position->getCompany(),
                'maxProjects' => $position->getMaxProjects(),
                'projectTags' => $position->getProjectTags() ?? [],
                'attributes' => array_map(fn($a) => $a->getId(), $position->getAttributes()->toArray()),
                'version' => $position->getVersion(),
                'accessRules' => array_map(fn($r) => [
                    'attributeId' => $r->getAttribute()->getId(),
                    'operator' => $r->getOperator(),
                    'value' => $r->getValue(),
                ], $position->getAccessRules()->toArray()),
            ],
            'availableAttributes' => $attributeArray,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_position_edit', methods: ['POST', 'PUT'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function edit(Position $position, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        // Optimistic locking check
        if (isset($data['version']) && $position->getVersion() !== (int)$data['version']) {
            return $this->json(['error' => 'Conflict: Document has been modified by someone else.'], 409);
        }

        if (isset($data['title'])) $position->setTitle($data['title']);
        if (isset($data['shortDescription'])) $position->setShortDescription($data['shortDescription']);
        if (isset($data['isPublic'])) $position->setIsPublic($data['isPublic']);
        if (isset($data['level'])) $position->setLevel($data['level']);
        if (isset($data['company'])) $position->setCompany($data['company']);
        if (isset($data['maxProjects'])) $position->setMaxProjects($data['maxProjects']);
        if (isset($data['projectTags']) && is_array($data['projectTags'])) {
            $position->setProjectTags($data['projectTags']);
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($position->getAttributes() as $attr) {
                $position->removeAttribute($attr);
            }
            foreach ($data['attributes'] as $attrId) {
                $attr = $em->getRepository(Attribute::class)->find($attrId);
                if ($attr) {
                    $position->addAttribute($attr);
                }
            }
        }

        if (isset($data['accessRules']) && is_array($data['accessRules'])) {
            foreach ($position->getAccessRules() as $existingRule) {
                $em->remove($existingRule);
            }
            $position->getAccessRules()->clear();

            foreach ($data['accessRules'] as $ruleData) {
                $attr = $em->getRepository(Attribute::class)->find($ruleData['attributeId']);
                if ($attr) {
                    $rule = new PositionAccessRule();
                    $rule->setPosition($position);
                    $rule->setAttribute($attr);
                    $rule->setOperator($ruleData['operator']);
                    $rule->setValue($ruleData['value']);
                    $em->persist($rule);
                }
            }
        }

        try {
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return $this->json(['error' => 'Conflict: Document has been modified by someone else.'], 409);
        }

        $this->addFlash('success', 'Position updated successfully.');
        return $this->redirectToRoute('app_position_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_position_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function delete(Position $position, EntityManagerInterface $em): Response
    {
        $em->remove($position);
        $em->flush();
        $this->addFlash('success', 'Position deleted successfully.');
        return $this->redirectToRoute('app_position_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/export', name: 'app_position_export', methods: ['GET'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function exportCsv(Position $position, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());
        $allCvs = $position->getCvs()->toArray();
        $cvs = array_filter($allCvs, function (\App\Entity\Cv $c) use ($isAdmin) {
            if ($isAdmin) return true;
            return $c->getStatus() === 'published';
        });

        $attributes = $position->getAttributes();

        // Fix N+1 query: batch fetch all candidate attribute values in a single query
        $candidateIds = array_filter(array_map(fn($cv) => $cv->getCandidate()?->getId(), $cvs));
        $valueMap = [];
        if (!empty($candidateIds)) {
            $qb = $em->createQueryBuilder();
            $rawValues = $qb->select('IDENTITY(v.candidate) AS cid', 'IDENTITY(v.attribute) AS aid', 'v.value')
                ->from(CandidateAttributeValue::class, 'v')
                ->where($qb->expr()->in('v.candidate', ':cids'))
                ->setParameter('cids', $candidateIds)
                ->getQuery()
                ->getArrayResult();

            foreach ($rawValues as $rv) {
                $val = $rv['value'];
                $displayVal = is_array($val) ? json_encode($val) : (string)$val;
                $valueMap[$rv['cid']][$rv['aid']] = $displayVal;
            }
        }

        $response = new StreamedResponse(function () use ($cvs, $attributes, $valueMap) {
            $handle = fopen('php://output', 'w+');

            // Header Row
            $headers = ['Candidate Name', 'Status', 'Likes', 'Created At'];
            foreach ($attributes as $attr) {
                $headers[] = $attr->getName();
            }
            fputcsv($handle, $headers);

            // Data Rows (no DB queries inside loop)
            foreach ($cvs as $cv) {
                $candidate = $cv->getCandidate();
                $cid = $candidate ? $candidate->getId() : 0;
                $row = [
                    ($candidate && $candidate->getUser()->getUserDetails()) ? ($candidate->getUser()->getUserDetails()->getFirstName() . ' ' . $candidate->getUser()->getUserDetails()->getLastName()) : 'Unknown',
                    $cv->getStatus(),
                    $cv->getLikes()->count(),
                    $cv->getCreatedAt() ? $cv->getCreatedAt()->format('Y-m-d H:i:s') : '',
                ];

                foreach ($attributes as $attr) {
                    $row[] = $valueMap[$cid][$attr->getId()] ?? '';
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="CVs_Position_' . $position->getId() . '.csv"');

        return $response;
    }
}
