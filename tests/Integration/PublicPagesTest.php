<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicPagesTest extends WebTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testPositionsPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/positions');

        $this->assertResponseIsSuccessful();
    }

    public function testSearchPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search?q=Engineer');

        $this->assertResponseIsSuccessful();
    }
}
