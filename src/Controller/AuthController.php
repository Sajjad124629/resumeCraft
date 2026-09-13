<?php

namespace App\Controller;

use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, InertiaService $inertia): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        $error =$authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $inertia->render('auth/Login', [
            'lastUsername' => $lastUsername,
            'error' => $error ? $error->getMessage() : null,
            'canResetPassword' => true,
            'status' => null,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, InertiaService $inertia): Response
    {
        if ($request->isMethod('POST')) {
            // Placeholder post logic: redirect to dashboard
            return $this->redirectToRoute('dashboard');
        }

        return $inertia->render('auth/Register');
    }

    #[Route('/forgot-password', name: 'password.request', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, InertiaService $inertia): Response
    {
        return $inertia->render('auth/ForgotPassword');
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): Response
    {
       throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
