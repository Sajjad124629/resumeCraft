<?php

namespace App\Controller;

use App\Entity\DiscussionPost;
use App\Entity\Position;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @method User|null getUser()
 */
#[Route('/discussions')]
class DiscussionController extends AbstractController
{
    #[Route('/position/{id}', name: 'app_discussion_post', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function postMessage(Position $position, Request $request, EntityManagerInterface $em, HubInterface $hub): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $content = $data['content'] ?? '';

        if (empty(trim($content))) {
            return $this->json(['error' => 'Empty message'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        $post = new DiscussionPost();
        $post->setPosition($position);
        $post->setAuthor($user);
        $post->setContent($content);

        $em->persist($post);
        $em->flush();

        // Publish to Mercure
        $authorProfile = $post->getAuthor()?->getCandidateProfile();
        $authorDetails = $post->getAuthor()?->getUserDetails();
        $authorName = $authorDetails ? ($authorDetails->getFirstName() . ' ' . $authorDetails->getLastName()) : ($post->getAuthor()->getEmail() ?? 'User');
        $payload = [
            'id' => $post->getId(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'candidateProfileId' => $authorProfile?->getId(),
                'name' => $authorName,
            ],
            'content' => $post->getContent(),
            'createdAt' => $post->getCreatedAt()->format('c'),
        ];

        try {
            $update = new Update(
                'position/' . $position->getId() . '/discussion',
                json_encode($payload)
            );
            $hub->publish($update);
        } catch (\Throwable $e) {
            // Mercure might be offline, ignore
        }

        return $this->json(['success' => true, 'post' => $payload]);
    }

    #[Route('/position/{id}', name: 'app_discussion_get', methods: ['GET'])]
    public function getMessages(Position $position, EntityManagerInterface $em): Response
    {
        $posts = $position->getDiscussionPosts();
        $data = [];
        foreach ($posts as $post) {
            $authorProfile = $post->getAuthor()?->getCandidateProfile();
            $authorDetails = $post->getAuthor()?->getUserDetails();
            $authorName = $authorDetails ? ($authorDetails->getFirstName() . ' ' . $authorDetails->getLastName()) : ($post->getAuthor()?->getEmail() ?? 'User');
            $data[] = [
                'id' => $post->getId(),
                'author' => [
                    'id' => $post->getAuthor()->getId(),
                    'candidateProfileId' => $authorProfile?->getId(),
                    'name' => $authorName,
                ],
                'content' => $post->getContent(),
                'createdAt' => $post->getCreatedAt()->format('c'),
            ];
        }

        return $this->json($data);
    }
}
