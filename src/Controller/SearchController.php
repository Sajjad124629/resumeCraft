<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Cv;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @method User|null getUser()
 */
class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, InertiaService $inertia, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q', '');

        $positionsData = [];
        $cvsData = [];

        if (!empty(trim($query))) {
            // Search Positions (title, company, description, projectTags)
            $positions = $em->getRepository(Position::class)
                ->createQueryBuilder('p')
                ->where('p.title LIKE :q OR p.company LIKE :q OR p.shortDescription LIKE :q OR p.projectTags LIKE :q')
                ->setParameter('q', '%' . $query . '%')
                ->getQuery()
                ->getResult();

            $positionsData = array_map(fn($p) => [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'company' => $p->getCompany(),
                'level' => $p->getLevel(),
                'shortDescription' => $p->getShortDescription(),
            ], $positions);

            // Search CVs
            /** @var User|null $user */
            $user = $this->getUser();
            $isRecruiter = $user && in_array('ROLE_RECRUITER', $user->getRoles());
            $isAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles());

            $cvQb = $em->getRepository(Cv::class)->createQueryBuilder('c')
                ->join('c.position', 'p')
                ->join('c.candidate', 'cp')
                ->join('cp.user', 'u')
                ->leftJoin('u.userDetails', 'ud')
                ->leftJoin('cp.projects', 'prj')
                ->distinct();

            // Searching by candidate name, position title, or project technologies/names
            $cvQb->where('ud.firstName LIKE :q OR ud.lastName LIKE :q OR p.title LIKE :q OR prj.name LIKE :q OR prj.description LIKE :q OR prj.tags LIKE :q')
                ->setParameter('q', '%' . $query . '%');

            if (!$isRecruiter && !$isAdmin) {
                // If standard user/candidate, only see published CVs or their own
                $candidateProfileId = $user && $user->getCandidateProfile() ? $user->getCandidateProfile()->getId() : null;
                if ($candidateProfileId) {
                    $cvQb->andWhere('c.status = :published OR cp.id = :myId')
                        ->setParameter('published', 'published')
                        ->setParameter('myId', $candidateProfileId);
                } else {
                    $cvQb->andWhere('c.status = :published')
                        ->setParameter('published', 'published');
                }
            }

            $cvs = $cvQb->getQuery()->getResult();

            $cvsData = array_map(function ($c) {
                $candidate = $c->getCandidate();
                $ud = $candidate->getUser()->getUserDetails();
                return [
                    'id' => $c->getId(),
                    'positionTitle' => $c->getPosition()->getTitle(),
                    'candidateName' => $ud ? ($ud->getFirstName() . ' ' . $ud->getLastName()) : 'Unknown',
                    'status' => $c->getStatus(),
                    'likes' => $c->getLikes()->count(),
                ];
            }, $cvs);
        }

        return $inertia->render('Search', [
            'query' => $query,
            'positions' => $positionsData,
            'cvs' => $cvsData,
        ]);
    }
}
