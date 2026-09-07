<?php

namespace App\Controller;

use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request, InertiaService $inertia): Response
    {
        if ($request->isMethod('POST')) {
            // Placeholder post logic: redirect to dashboard
            return $this->redirectToRoute('dashboard');
        }

        return $inertia->render('auth/Login', [
            'canResetPassword' => true,
            'status' => null,
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
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

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): Response
    {
        return $this->redirectToRoute('login');
    }
}
