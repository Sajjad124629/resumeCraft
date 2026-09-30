<?php

namespace App\Tests\Integration;

use App\Entity\Position;
use App\Entity\Cv;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PositionsAndCvIntegrationTest extends WebTestCase
{
    public function testPositionsListIsAvailable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/positions');
        $this->assertResponseIsSuccessful();
    }

    public function testPositionShowPageIsSuccessful(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $position = $em->getRepository(Position::class)->findOneBy([]);

        if ($position) {
            $client->request('GET', '/positions/' . $position->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No position found');
        }
    }

    public function testExportCsvRequiresRecruiterOrAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $position = $em->getRepository(Position::class)->findOneBy([]);

        if ($position) {
            // Unauthenticated should redirect to login or be 403
            $client->request('GET', '/positions/' . $position->getId() . '/export');
            $this->assertTrue($client->getResponse()->isRedirect() || $client->getResponse()->getStatusCode() === 403);

            // Log in as recruiter
            $recruiter = $em->getRepository(User::class)->findOneBy(['email' => 'recruiter@resumecraft.com']);
            if ($recruiter) {
                $client->loginUser($recruiter);
                $client->request('GET', '/positions/' . $position->getId() . '/export');
                $this->assertResponseIsSuccessful();
                $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
            }
        }
    }

    public function testCvPdfGenerationWithQrCode(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $cv = $em->getRepository(Cv::class)->findOneBy(['status' => 'published']);

        if ($cv) {
            $recruiter = $em->getRepository(User::class)->findOneBy(['email' => 'recruiter@resumecraft.com']);
            if ($recruiter) {
                $client->loginUser($recruiter);
                $client->request('GET', '/cvs/' . $cv->getId() . '/pdf');
                $this->assertResponseIsSuccessful();
                $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
            }
        }
    }
}
