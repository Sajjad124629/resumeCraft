<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Cv;
use App\Entity\User;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(InertiaService $inertia, EntityManagerInterface $em): Response
    {
        // 1. Statistics
        $totalPositions = $em->getRepository(Position::class)->count([]);
        $totalCvs = $em->getRepository(Cv::class)->count([]);
        
        // Count roles
        $users = $em->getRepository(User::class)->findAll();
        $totalCandidates = 0;
        $totalRecruiters = 0;
        foreach ($users as $u) {
            $roles = $u->getRoles();
            if (in_array('ROLE_CANDIDATE', $roles)) $totalCandidates++;
            if (in_array('ROLE_RECRUITER', $roles)) $totalRecruiters++;
        }

        // CVs last 24 hours
        $yesterday = new \DateTime('-24 hours');
        $cvsLast24h = $em->getRepository(Cv::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getSingleScalarResult();

        $stats = [
            'totalPositions' => $totalPositions,
            'totalCandidates' => $totalCandidates,
            'totalRecruiters' => $totalRecruiters,
            'totalCvs' => $totalCvs,
            'cvsLast24h' => $cvsLast24h,
        ];

        // 2. Latest Positions (Top 5 by ID desc assuming ID is chronological)
        $latestPositions = $em->getRepository(Position::class)->findBy([], ['id' => 'DESC'], 5);
        $latestPositionsData = array_map(fn($p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'company' => $p->getCompany(),
            'level' => $p->getLevel(),
        ], $latestPositions);

        // 3. Most Popular Positions (Top 5 by CV count)
        $popularPositionsQuery = $em->getRepository(Position::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.cvs', 'c')
            ->select('p AS position', 'COUNT(c.id) AS cv_count')
            ->groupBy('p.id')
            ->orderBy('cv_count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
            
        $popularPositionsData = array_map(fn($item) => [
            'id' => $item['position']->getId(),
            'title' => $item['position']->getTitle(),
            'company' => $item['position']->getCompany(),
            'cvCount' => $item['cv_count'],
        ], $popularPositionsQuery);

        // 4. Tag Cloud
        $projects = $em->getRepository(Project::class)->findAll();
        $tagCounts = [];
        foreach ($projects as $project) {
            $tags = $project->getTags() ?? [];
            foreach ($tags as $tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
        arsort($tagCounts);
        $tagCloud = [];
        foreach (array_slice($tagCounts, 0, 20) as $tag => $count) {
            $tagCloud[] = ['tag' => $tag, 'count' => $count];
        }

        return $inertia->render('Home', [
            'app_name' => 'ResumeCraft',
            'stats' => $stats,
            'latestPositions' => $latestPositionsData,
            'popularPositions' => $popularPositionsData,
            'tagCloud' => $tagCloud,
        ]);
    }
}
