<?php

namespace App\Controller;

use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(InertiaService $inertia, EntityManagerInterface $em): Response
    {
        $conn = $em->getConnection();

        // Stats
        $stats = [
            'totalPositions' => $em->getRepository(\App\Entity\Position::class)->count([]),
            'totalCandidates' => $em->getRepository(\App\Entity\CandidateProfile::class)->count([]),
            'totalRecruiters' => 0, 
            'totalCvs' => $em->getRepository(\App\Entity\Cv::class)->count([]),
            'cvs24h' => 0,
        ];
        
        $recruiterQuery = $em->createQuery("SELECT count(u.id) FROM App\Entity\User u JOIN u.role r WHERE r.slug = 'ROLE_RECRUITER'");
        $stats['totalRecruiters'] = $recruiterQuery->getSingleScalarResult();

        $yesterday = new \DateTimeImmutable('-24 hours');
        $cv24hQuery = $em->createQuery("SELECT count(c.id) FROM App\Entity\Cv c WHERE c.createdAt >= :yesterday")
            ->setParameter('yesterday', $yesterday);
        $stats['cvs24h'] = $cv24hQuery->getSingleScalarResult();

        // Latest Positions
        $latestPositions = $em->getRepository(\App\Entity\Position::class)->findBy(
            [],
            ['id' => 'DESC'],
            5
        );

        // Map to arrays
        $latestPositionsArray = array_map(fn($p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'shortDescription' => $p->getShortDescription(),
            'company' => $p->getCompany(),
            'level' => $p->getLevel(),
        ], $latestPositions);

        // Most Popular Positions (Top 5)
        $popularPositionsQuery = $em->createQuery("
            SELECT p.id, p.title, p.company, COUNT(c.id) as cvCount
            FROM App\Entity\Position p
            LEFT JOIN p.cvs c
            GROUP BY p.id, p.title, p.company
            ORDER BY cvCount DESC
        ")->setMaxResults(5);
        $popularPositions = $popularPositionsQuery->getArrayResult();

        // Tag Cloud
        $projects = $em->getRepository(\App\Entity\Project::class)->findAll();
        $tagFrequencies = [];
        foreach ($projects as $project) {
            $tags = $project->getTags() ?? [];
            foreach ($tags as $tag) {
                if (!isset($tagFrequencies[$tag])) {
                    $tagFrequencies[$tag] = 0;
                }
                $tagFrequencies[$tag]++;
            }
        }
        
        $tagsData = [];
        foreach ($tagFrequencies as $name => $count) {
            $tagsData[] = [
                'name' => $name,
                'weight' => $count // Can be used for visual scaling in UI
            ];
        }

        return $inertia->render('Dashboard', [
            'app_name' => 'ResumeCraft',
            'stats' => $stats,
            'latestPositions' => $latestPositionsArray,
            'popularPositions' => $popularPositions,
            'tags' => $tagsData,
        ]);
    }
}
