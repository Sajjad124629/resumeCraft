<?php

namespace App\Controller;

use App\Entity\Cv;
use App\Entity\User;
use App\Entity\Position;
use App\Entity\CandidateAttributeValue;
use App\Entity\CvLike;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * @method User|null getUser()
 */
#[Route('/cvs')]
class CvController extends AbstractController
{
    #[Route('/position/{id}/generate', name: 'app_cv_generate', methods: ['POST'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function generate(Position $position, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $candidate = $user?->getCandidateProfile();
        if (!$candidate) {
            throw $this->createAccessDeniedException('Candidate profile required.');
        }

        // Check Access Rules
        if (!$position->isPublic()) {
            $vals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
            $candidateAttrMap = [];
            foreach ($vals as $v) {
                $candidateAttrMap[$v->getAttribute()->getId()] = $v->getValue();
            }

            foreach ($position->getAccessRules() as $rule) {
                $attrId = $rule->getAttribute()->getId();
                $candValue = $candidateAttrMap[$attrId] ?? null;
                $ruleValue = $rule->getValue();
                $op = $rule->getOperator();

                if ($candValue === null) {
                    throw $this->createAccessDeniedException('You do not meet the access requirements for this position.');
                }
                if ($op === '=' && (string)$candValue !== (string)$ruleValue) {
                    throw $this->createAccessDeniedException('You do not meet the access requirements for this position.');
                }
                if ($op === '>' && (float)$candValue <= (float)$ruleValue) {
                    throw $this->createAccessDeniedException('You do not meet the access requirements for this position.');
                }
                if ($op === '<' && (float)$candValue >= (float)$ruleValue) {
                    throw $this->createAccessDeniedException('You do not meet the access requirements for this position.');
                }
            }
        }

        // Check if CV already exists
        $existingCv = $em->getRepository(Cv::class)->findOneBy([
            'candidate' => $candidate,
            'position' => $position
        ]);

        if ($existingCv) {
            return $this->redirectToRoute('app_cv_show', ['id' => $existingCv->getId()]);
        }

        $cv = new Cv();
        $cv->setCandidate($candidate);
        $cv->setPosition($position);
        $cv->setStatus('draft');

        $em->persist($cv);
        $em->flush();

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/{id}', name: 'app_cv_show', methods: ['GET'])]
    public function show(Cv $cv, InertiaService $inertia, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        $isOwner = $user->getCandidateProfile() === $cv->getCandidate();
        $isRecruiter = in_array('ROLE_RECRUITER', $user->getRoles());
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOwner && !$isAdmin && ($cv->getStatus() !== 'published' || !$isRecruiter)) {
            throw $this->createAccessDeniedException('You cannot view this CV.');
        }

        $candidate = $cv->getCandidate();
        $position = $cv->getPosition();

        $requestedAttributes = $position->getAttributes();

        // Batch pre-fetch all candidate attribute values (no N+1 queries)
        $allVals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $valMap = [];
        foreach ($allVals as $av) {
            $valMap[$av->getAttribute()->getId()] = $av;
        }

        $attributesData = [];
        foreach ($requestedAttributes as $attr) {
            $valEntity = $valMap[$attr->getId()] ?? null;

            $attributesData[] = [
                'attributeId' => $attr->getId(),
                'name' => $attr->getName(),
                'type' => $attr->getType(),
                'options' => $attr->getOptions(),
                'value' => $valEntity ? $valEntity->getValue() : null,
                'valVersion' => $valEntity ? $valEntity->getVersion() : 1,
            ];
        }

        // Filter candidate projects based on position projectTags
        $allProjects = $candidate->getProjects()->toArray();
        $targetTags = $position->getProjectTags() ?? [];
        if (!empty($targetTags)) {
            $lowerTargetTags = array_map('strtolower', $targetTags);
            $filtered = array_filter($allProjects, function ($p) use ($lowerTargetTags) {
                $pTags = array_map('strtolower', $p->getTags() ?? []);
                return count(array_intersect($lowerTargetTags, $pTags)) > 0;
            });
            $selectedProjects = !empty($filtered) ? array_values($filtered) : $allProjects;
        } else {
            $selectedProjects = $allProjects;
        }

        $projects = array_slice($selectedProjects, 0, $position->getMaxProjects());
        $projectsData = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'tags' => $p->getTags() ?? [],
        ], $projects);

        $hasLiked = false;
        if ($isRecruiter) {
            $like = $em->getRepository(CvLike::class)->findOneBy([
                'cv' => $cv,
                'recruiter' => $user
            ]);
            $hasLiked = $like !== null;
        }

        return $inertia->render('cv/Show', [
            'cv' => [
                'id' => $cv->getId(),
                'status' => $cv->getStatus(),
                'likes' => $cv->getLikes()->count(),
                'hasLiked' => $hasLiked,
                'position' => [
                    'id' => $position->getId(),
                    'title' => $position->getTitle(),
                    'company' => $position->getCompany(),
                ],
                'candidate' => [
                    'id' => $candidate->getId(),
                    'firstName' => $candidate->getUser()->getUserDetails()?->getFirstName(),
                    'lastName' => $candidate->getUser()->getUserDetails()?->getLastName(),
                    'location' => $candidate->getLocation(),
                    'photo' => $candidate->getUser()->getUserDetails()?->getPhoto(),
                ],
                'attributes' => $attributesData,
                'projects' => $projectsData,
                'version' => $cv->getVersion(),
            ],
            'isOwner' => $isOwner,
            'isReadOnly' => !$isOwner && !$isAdmin,
        ]);
    }

    #[Route('/batch-delete', name: 'app_cv_batch_delete', methods: ['POST', 'DELETE'])]
    public function batchDelete(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->getPayload()->all() ?: (json_decode($request->getContent(), true) ?? $request->request->all());
        $ids = $data['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $count = 0;
        foreach ($ids as $id) {
            $cv = $em->getRepository(Cv::class)->find((int)$id);
            if ($cv) {
                $isOwner = $user->getCandidateProfile() === $cv->getCandidate();
                if ($isOwner || $isAdmin) {
                    $em->remove($cv);
                    $count++;
                }
            }
        }
        $em->flush();

        $this->addFlash('success', sprintf('%d CV(s) deleted successfully.', $count));
        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_cv_delete', methods: ['DELETE', 'POST'], requirements: ['id' => '\d+'])]
    public function delete(Cv $cv, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isOwner = $user->getCandidateProfile() === $cv->getCandidate();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOwner && !$isAdmin) {
            throw $this->createAccessDeniedException('You are not authorized to delete this CV.');
        }

        $em->remove($cv);
        $em->flush();

        $this->addFlash('success', 'CV deleted successfully.');
        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/publish', name: 'app_cv_publish', methods: ['POST'])]
    public function publish(Cv $cv, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($user->getCandidateProfile() !== $cv->getCandidate() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Not your CV');
        }

        $cv->setStatus('published');
        $em->flush();
        // CV PUBLISH GATE: Verify 100% of position's required attributes have non-empty values
        $position = $cv->getPosition();
        $candidate = $cv->getCandidate();
        $allVals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $valMap = [];
        foreach ($allVals as $av) {
            $valMap[$av->getAttribute()->getId()] = $av->getValue();
        }

        foreach ($position->getAttributes() as $attr) {
            $val = $valMap[$attr->getId()] ?? null;
            if ($val === null || $val === '' || ($attr->getType() === 'period' && is_array($val) && empty($val['start']))) {
                $this->addFlash('error', "Cannot publish CV: Required attribute '{$attr->getName()}' is missing or empty.");
                return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
            }
        }

        try {
            $cv->setStatus('published');
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return $this->json(['error' => 'Conflict: CV was modified elsewhere.'], 409);
        }

        $this->addFlash('success', 'CV published successfully.');
        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/{id}/unpublish', name: 'app_cv_unpublish', methods: ['POST'])]
    public function unpublish(Cv $cv, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($user->getCandidateProfile() !== $cv->getCandidate() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Not your CV');
        }

        $cv->setStatus('draft');
        $em->flush();

        return $this->redirectToRoute('app_cv_show', ['id' => $cv->getId()]);
    }

    #[Route('/{id}/public', name: 'app_cv_public_show', methods: ['GET'])]
    public function publicShow(Cv $cv, InertiaService $inertia, EntityManagerInterface $em): Response
    {
        if ($cv->getStatus() !== 'published') {
            throw $this->createNotFoundException('This CV is not published.');
        }

        $candidate = $cv->getCandidate();
        $position = $cv->getPosition();

        $requestedAttributes = $position->getAttributes();
        $allVals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $valMap = [];
        foreach ($allVals as $av) {
            $valMap[$av->getAttribute()->getId()] = $av;
        }

        $attributesData = [];
        foreach ($requestedAttributes as $attr) {
            $valEntity = $valMap[$attr->getId()] ?? null;
            $attributesData[] = [
                'name' => $attr->getName(),
                'type' => $attr->getType(),
                'value' => $valEntity ? $valEntity->getValue() : null,
            ];
        }

        $allProjects = $candidate->getProjects()->toArray();
        $targetTags = $position->getProjectTags() ?? [];
        if (!empty($targetTags)) {
            $lowerTargetTags = array_map('strtolower', $targetTags);
            $filtered = array_filter($allProjects, function ($p) use ($lowerTargetTags) {
                $pTags = array_map('strtolower', $p->getTags() ?? []);
                return count(array_intersect($lowerTargetTags, $pTags)) > 0;
            });
            $selectedProjects = !empty($filtered) ? array_values($filtered) : $allProjects;
        } else {
            $selectedProjects = $allProjects;
        }

        $projects = array_slice($selectedProjects, 0, $position->getMaxProjects());
        $projectsData = array_map(fn($p) => [
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'tags' => $p->getTags() ?? [],
            'periodStart' => $p->getDateStart() ? $p->getDateStart()->format('Y-m-d') : null,
            'periodEnd' => $p->getDateEnd() ? $p->getDateEnd()->format('Y-m-d') : null,
        ], $projects);

        return $inertia->render('cv/PublicShow', [
            'cv' => [
                'id' => $cv->getId(),
                'likes' => $cv->getLikes()->count(),
                'position' => [
                    'title' => $position->getTitle(),
                    'company' => $position->getCompany(),
                ],
                'candidate' => [
                    'firstName' => $candidate->getUser()->getUserDetails()?->getFirstName(),
                    'lastName' => $candidate->getUser()->getUserDetails()?->getLastName(),
                    'location' => $candidate->getLocation(),
                    'photo' => $candidate->getUser()->getUserDetails()?->getPhoto(),
                ],
                'attributes' => $attributesData,
                'projects' => $projectsData,
            ],
        ]);
    }

    #[Route('/{id}/like', name: 'app_cv_like', methods: ['POST'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function like(Cv $cv, EntityManagerInterface $em): Response
    {
        /** @var User|null $recruiter */
        $recruiter = $this->getUser();
        $like = $em->getRepository(CvLike::class)->findOneBy([
            'cv' => $cv,
            'recruiter' => $recruiter
        ]);

        if ($like) {
            $em->remove($like);
        } else {
            $like = new CvLike();
            $like->setCv($cv);
            $like->setRecruiter($recruiter);
            $em->persist($like);
        }
        $em->flush();

        return $this->json(['likes' => $cv->getLikes()->count()]);
    }

    #[Route('/{id}/pdf', name: 'app_cv_pdf', methods: ['GET'])]
    public function downloadPdf(Cv $cv, EntityManagerInterface $em): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $isOwner = $user && $user->getCandidateProfile() === $cv->getCandidate();
        $isRecruiter = $user && in_array('ROLE_RECRUITER', $user->getRoles());
        $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());

        if (!$isOwner && !$isAdmin && ($cv->getStatus() !== 'published' || !$isRecruiter)) {
            throw $this->createAccessDeniedException('You cannot download this CV.');
        }

        $candidate = $cv->getCandidate();
        $position = $cv->getPosition();

        $requestedAttributes = $position->getAttributes();
        $allVals = $em->getRepository(CandidateAttributeValue::class)->findBy(['candidate' => $candidate]);
        $valMap = [];
        foreach ($allVals as $av) {
            $valMap[$av->getAttribute()->getId()] = $av;
        }

        $attributesData = [];
        foreach ($requestedAttributes as $attr) {
            $valEntity = $valMap[$attr->getId()] ?? null;
            $rawVal = $valEntity ? $valEntity->getValue() : null;
            $formattedVal = $rawVal;
            if ($attr->getType() === 'boolean') {
                $formattedVal = $rawVal ? 'Yes' : 'No';
            } elseif ($attr->getType() === 'period' && is_array($rawVal)) {
                $formattedVal = ($rawVal['start'] ?? '') . ' to ' . ($rawVal['end'] ?? 'Present');
            } elseif (is_array($rawVal)) {
                $formattedVal = json_encode($rawVal);
            }

            $attributesData[] = [
                'attribute' => ['id' => $attr->getId()],
                'name' => $attr->getName(),
                'type' => $attr->getType(),
                'value' => $formattedVal,
            ];
        }

        $allProjects = $candidate->getProjects()->toArray();
        $targetTags = $position->getProjectTags() ?? [];
        if (!empty($targetTags)) {
            $lowerTargetTags = array_map('strtolower', $targetTags);
            $filtered = array_filter($allProjects, function ($p) use ($lowerTargetTags) {
                $pTags = array_map('strtolower', $p->getTags() ?? []);
                return count(array_intersect($lowerTargetTags, $pTags)) > 0;
            });
            $selectedProjects = !empty($filtered) ? array_values($filtered) : $allProjects;
        } else {
            $selectedProjects = $allProjects;
        }

        $projects = array_slice($selectedProjects, 0, $position->getMaxProjects());

        $photo = $candidate->getUser()->getUserDetails()?->getPhoto();
        $candidatePhoto = null;
        if ($photo) {
            if (str_starts_with($photo, 'data:image')) {
                $candidatePhoto = $photo;
            } elseif (filter_var($photo, FILTER_VALIDATE_URL)) {
                try {
                    $context = stream_context_create([
                        'http' => ['timeout' => 3],
                        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
                    ]);
                    $imgContent = @file_get_contents($photo, false, $context);
                    if ($imgContent) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($imgContent) ?: 'image/jpeg';
                        $candidatePhoto = 'data:' . $mime . ';base64,' . base64_encode($imgContent);
                    } else {
                        $candidatePhoto = $photo;
                    }
                } catch (\Throwable $e) {
                    $candidatePhoto = $photo;
                }
            } elseif (file_exists($photo)) {
                $imgContent = @file_get_contents($photo);
                if ($imgContent) {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($imgContent) ?: 'image/jpeg';
                    $candidatePhoto = 'data:' . $mime . ';base64,' . base64_encode($imgContent);
                }
            } else {
                $candidatePhoto = $photo;
            }
        }

        $cvData = [
            'candidateName' => $candidate->getUser()->getUserDetails()?->getFirstName() . ' ' . $candidate->getUser()->getUserDetails()?->getLastName(),
            'candidatePhoto' => $candidatePhoto,
            'location' => $candidate->getLocation(),
            'position' => ['title' => $position->getTitle(), 'attributes' => $requestedAttributes],
            'attributes' => $attributesData,
            'projects' => $projects,
        ];

        // Generate QR Code
        $publicUrl = $this->generateUrl('app_cv_public_show', ['id' => $cv->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $publicUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = extension_loaded('gd') ? new PngWriter() : new SvgWriter();
        $result = $writer->write($qrCode);
        $qrCodeBase64 = $result->getDataUri();

        $html = $this->renderView('cv/pdf.html.twig', [
            'cv' => $cvData,
            'qrCodeBase64' => $qrCodeBase64,
        ]);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="CV_' . $candidate->getUser()->getUserDetails()?->getLastName() . '.pdf"',
        ]);
    }
}
