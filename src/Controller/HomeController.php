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
        $totalPositions = $em->getRepository(Position::class)->count([]);
        $totalCvs = $em->getRepository(Cv::class)->count([]);

        $users = $em->getRepository(User::class)->findAll();
        $totalCandidates = 0;
        $totalRecruiters = 0;
        foreach ($users as $u) {
            $roles = $u->getRoles();
            if (in_array('ROLE_CANDIDATE', $roles)) $totalCandidates++;
            if (in_array('ROLE_RECRUITER', $roles)) $totalRecruiters++;
        }

        $yesterday = new \DateTime('-24 hours');
        $cvsLast24h = $em->getRepository(Cv::class)
            ->countCvsSince($yesterday);

        $stats = [
            'totalPositions' => $totalPositions,
            'totalCandidates' => $totalCandidates,
            'totalRecruiters' => $totalRecruiters,
            'totalCvs' => $totalCvs,
            'cvsLast24h' => $cvsLast24h,
        ];

        $latestPositionsData = $em->getRepository(Position::class)->findLatestSummary(5);
        $popularPositionsData = $em->getRepository(Position::class)->findPopularSummary(5);

        $tagCloud = $em->getRepository(Project::class)->getTags(20);

        return $inertia->render('Home', [
            'app_name' => 'ResumeCraft',
            'stats' => $stats,
            'latestPositions' => $latestPositionsData,
            'popularPositions' => $popularPositionsData,
            'tagCloud' => $tagCloud,
        ]);
    }
}
