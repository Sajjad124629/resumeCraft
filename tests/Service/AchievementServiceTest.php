<?php

namespace App\Tests\Service;

use App\Entity\CandidateProfile;
use App\Entity\Cv;
use App\Entity\CvLike;
use App\Entity\Project;
use App\Entity\User;
use App\Service\AchievementService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class AchievementServiceTest extends TestCase
{
    public function testGetAchievementsWithMilestones(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $cvRepo = $this->createMock(EntityRepository::class);

        $em->method('getRepository')
            ->with(Cv::class)
            ->willReturn($cvRepo);

        $profile = new CandidateProfile();
        $user = new User();
        $profile->setUser($user);

        // Add 5 projects
        for ($i = 0; $i < 5; $i++) {
            $p = new Project();
            $p->setName("Project $i");
            $profile->getProjects()->add($p);
        }

        // Mock 1 CV with 1 like
        $cv = new Cv();
        $like = new CvLike();
        $cv->getLikes()->add($like);

        $cvRepo->method('findBy')
            ->with(['candidate' => $profile])
            ->willReturn([$cv]);

        $service = new AchievementService($em);
        $achievements = $service->getAchievements($profile);

        $names = array_column($achievements, 'name');
        $this->assertContains('5 Projects', $names);
        $this->assertContains('First CV', $names);
        $this->assertContains('First Like', $names);
        $this->assertNotContains('10 Projects', $names);
        $this->assertNotContains('25 Likes', $names);
    }
}
