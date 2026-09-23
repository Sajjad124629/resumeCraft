<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\CandidateProfile;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use League\OAuth2\Client\Provider\GoogleUser;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $router;
    private $passwordHasher;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserPasswordHasherInterface $passwordHasher)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    if (!$existingUser->getGoogleId()) {
                        $existingUser->setGoogleId($googleUser->getId());
                    }
                    if (!$existingUser->isVerified()) {
                        $existingUser->setIsVerified(true);
                    }
                    $this->entityManager->flush();
                    return $existingUser;
                }

                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleUser->getId());
                $user->setIsVerified(true);

                // Random password since they use Google, properly hashed
                $randomPassword = bin2hex(random_bytes(16));
                $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));

                $roleCandidate = $this->entityManager->getRepository(Role::class)->findOneBy(['slug' => 'ROLE_CANDIDATE']);
                if ($roleCandidate) {
                    $user->setRole($roleCandidate);
                }

                $profile = new CandidateProfile();
                $profile->setUser($user);

                $details = new \App\Entity\UserDetails();
                $details->setUser($user);
                $details->setFirstName($googleUser->getFirstName() ?? 'Google');
                $details->setLastName($googleUser->getLastName() ?? 'User');
                $details->setPhoto($googleUser->getAvatar());

                $this->entityManager->persist($user);
                $this->entityManager->persist($profile);
                $this->entityManager->persist($details);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session instanceof \Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', $message);
            }
        }
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/login',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
