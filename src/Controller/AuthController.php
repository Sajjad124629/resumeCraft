<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\User;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * @method User|null getUser()
 */
class AuthController extends AbstractController
{
    public function __construct(
        private \Symfony\Bundle\SecurityBundle\Security $security
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, InertiaService $inertia): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $inertia->render('auth/Login', [
            'last_username' => $lastUsername,
            'error' => $error ? $error->getMessage() : null,
            'status' => null,
            'canResetPassword' => true,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        InertiaService $inertia,
        VerifyEmailHelperInterface $verifyEmailHelper,
        MailerInterface $mailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $name = trim($data['name'] ?? ($data['firstName'] ?? ''));

            if (!$email) {
                return $inertia->render('auth/Register', [
                    'errors' => ['email' => 'Email is required.']
                ]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $inertia->render('auth/Register', [
                    'errors' => ['email' => 'Please enter a valid email address.']
                ]);
            }

            if (!$password || strlen($password) < 6) {
                return $inertia->render('auth/Register', [
                    'errors' => ['password' => 'Password must be at least 6 characters.']
                ]);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                return $inertia->render('auth/Register', [
                    'errors' => ['email' => 'An account with this email already exists.']
                ]);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $password
                )
            );
            $user->setIsVerified(false);

            $roleCandidate = $entityManager->getRepository(\App\Entity\Role::class)->findOneBy(['slug' => 'ROLE_CANDIDATE']);
            if ($roleCandidate) {
                $user->setRole($roleCandidate);
            }

            $profile = new CandidateProfile();
            $profile->setUser($user);

            $details = new \App\Entity\UserDetails();
            $details->setUser($user);
            $nameParts = explode(' ', $name, 2);
            $details->setFirstName($nameParts[0] ?? $name);
            $details->setLastName($nameParts[1] ?? '');

            $entityManager->persist($user);
            $entityManager->persist($profile);
            $entityManager->persist($details);
            $entityManager->flush();

            // Send Verification Email with beautiful HTML template
            try {
                $signatureComponents = $verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    (string) $user->getId(),
                    $user->getEmail(),
                    ['id' => $user->getId()]
                );

                $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'sajjadhossainridoy83@gmail.com';
                $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'ResumeCraft';

                $emailMessage = (new TemplatedEmail())
                    ->from(new Address($fromAddress, $fromName))
                    ->to($user->getEmail())
                    ->subject('Confirm your Email - ResumeCraft')
                    ->htmlTemplate('emails/verify_email.html.twig')
                    ->context([
                        'userName' => $name ?: 'there',
                        'signedUrl' => $signatureComponents->getSignedUrl(),
                    ]);

                $mailer->send($emailMessage);
            } catch (\Throwable $e) {
                error_log('Mailer error during registration: ' . $e->getMessage());
            }

            // Auto-login newly registered user
            try {
                $response = $this->security->login($user, 'form_login', 'main');
                if ($response) {
                    return $response;
                }
            } catch (\Throwable $e) {
                try {
                    $this->security->login($user);
                } catch (\Throwable $e2) {
                    // ignore
                }
            }

            $this->addFlash('success', 'Registration successful! Welcome to your dashboard.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $inertia->render('auth/Register');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, \App\Repository\UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        $this->addFlash('success', 'Your email address has been verified successfully!');

        return $this->getUser() ? $this->redirectToRoute('app_dashboard') : $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'password.request', methods: ['GET', 'POST'])]
    public function forgotPassword(InertiaService $inertia): Response
    {
        return $inertia->render('auth/ForgotPassword');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
