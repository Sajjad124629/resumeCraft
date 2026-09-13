<?php

namespace App\Controller;

use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(InertiaService $inertia): Response
    {
        return $inertia->render('Dashboard', [
            'app_name' => 'Symfony Inertia App',
        ]);
    }
}
