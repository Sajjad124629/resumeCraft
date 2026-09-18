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
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * @method User|null getUser()
 */
class AuthController extends AbstractController
{
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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, InertiaService $inertia, VerifyEmailHelperInterface $verifyEmailHelper, MailerInterface $mailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $user = new User();
            $user->setEmail($data['email'] ?? '');
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $data['password'] ?? ''
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
            $details->setFirstName($data['firstName'] ?? ($data['name'] ?? ''));
            $details->setLastName($data['lastName'] ?? '');

            $entityManager->persist($user);
            $entityManager->persist($profile);
            $entityManager->persist($details);
            $entityManager->flush();

            // Generate Verification Email URL
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            // Send Email
            $email = (new Email())
                ->from('no-reply@cvproject.local')
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->html('<p>Please confirm your email by clicking this link: <a href="' . $signatureComponents->getSignedUrl() . '">Confirm my Email</a></p>');

            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                // Ignore in dev if mailer fails
            }

            $this->addFlash('success', 'Registration successful. Please check your email to verify your account.');
            return $this->redirectToRoute('app_login');
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

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_login');
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
