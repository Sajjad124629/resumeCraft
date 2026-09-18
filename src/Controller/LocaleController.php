<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Cookie;

class LocaleController extends AbstractController
{
    #[Route('/locale/{code}', name: 'app_locale_switch', methods: ['POST'])]
    public function switchLocale(string $code, Request $request): Response
    {
        $validLocales = ['en', 'es'];

        $referer = $request->headers->get('referer') ?: '/dashboard';
        $response = $this->redirect($referer, Response::HTTP_SEE_OTHER);

        if (in_array($code, $validLocales)) {
            $request->getSession()->set('_locale', $code);
            $response->headers->setCookie(new Cookie('_locale', $code, time() + (86400 * 365), '/', null, false, false));
        }

        return $response;
    }
}
