<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\Cv;
use App\Entity\Position;
use App\Entity\Project;
use App\Entity\User;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(InertiaService $inertia, EntityManagerInterface $em): Response
    {
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
        // Stats
        $stats = [
            'totalPositions' => $em->getRepository(Position::class)->count([]),
            'totalCandidates' => $totalCandidates,
            'totalRecruiters' => $totalRecruiters,
            'totalCvs' => $em->getRepository(Cv::class)->count([]),
            'cvs24h' => $cvsLast24h,
        ];
        $latestPositionsData = $em->getRepository(Position::class)->findLatestSummary(5);
        $popularPositionsData = $em->getRepository(Position::class)->findPopularSummary(5);

        // Tag Cloud
        $tagCloud = $em->getRepository(Project::class)->getTags(20);
        return $inertia->render('Dashboard', [
            'app_name' => 'ResumeCraft',
            'stats' => $stats,
            'latestPositions' => $latestPositionsData,
            'popularPositions' => $popularPositionsData,
            'tags' => $tagCloud,
        ]);
    }
}
