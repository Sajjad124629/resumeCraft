<?php

namespace App\Service;

use App\Entity\CandidateProfile;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Cv;
use App\Entity\CvLike;

class AchievementService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getAchievements(CandidateProfile $profile): array
    {
        $projectsCount = $profile->getProjects()->count();
        
        $cvs = $this->em->getRepository(Cv::class)->findBy(['candidate' => $profile]);
        $cvsCount = count($cvs);

        $likesCount = 0;
        foreach ($cvs as $cv) {
            $likesCount += $cv->getLikes()->count();
        }

        $achievements = [];

        if ($projectsCount >= 5) {
            $achievements[] = ['name' => '5 Projects', 'icon' => '🚀', 'description' => 'Created 5+ projects'];
        }
        if ($projectsCount >= 10) {
            $achievements[] = ['name' => '10 Projects', 'icon' => '🏆', 'description' => 'Created 10+ projects'];
        }

        if ($cvsCount >= 1) {
            $achievements[] = ['name' => 'First CV', 'icon' => '📄', 'description' => 'Created first CV'];
        }
        if ($cvsCount >= 5) {
            $achievements[] = ['name' => '5 CVs', 'icon' => '📑', 'description' => 'Created 5+ CVs'];
        }

        if ($likesCount >= 1) {
            $achievements[] = ['name' => 'First Like', 'icon' => '❤️', 'description' => 'Received a like on a CV'];
        }
        if ($likesCount >= 25) {
            $achievements[] = ['name' => '25 Likes', 'icon' => '🔥', 'description' => 'Received 25+ likes on CVs'];
        }

        return $achievements;
    }
}
