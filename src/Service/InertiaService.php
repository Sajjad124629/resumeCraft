<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class InertiaService
{
    public function __construct(
        private RequestStack $requestStack,
        private Environment $twig,
        private RouterInterface $router,
        private string $rootView = 'app.html.twig',
        private array $sharedProps = []
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->sharedProps[$key] = $value;
    }

    private function getRoutes(): array
    {
        $routes = [];
        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if (str_starts_with($name, '_') || str_starts_with($name, 'pentatrion')) {
                continue;
            }
            $routes[$name] = $route->getPath();
        }
        return $routes;
    }

    public function render(string $component, array $props = []): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        
        $defaultProps = [
            'routes' => $this->getRoutes(),
            'settings' => [
                'title' => 'VR ERP',
                'logo' => null,
            ],
            'auth' => [
                'user' => [
                    'email' => 'admin@example.com',
                    'user_detail' => [
                        'fullname' => 'Administrator',
                        'image' => null,
                    ]
                ]
            ],
            'flash' => [],
            'languages' => [],
            'locale' => 'en',
            'dir' => 'ltr',
        ];
        
        $props = array_merge($defaultProps, $this->sharedProps, $props);

        $page = [
            'component' => $component,
            'props' => $props,
            'url' => $request->getRequestUri(),
            'version' => null,
        ];

        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, 200, ['X-Inertia' => 'true']);
        }

        $html = $this->twig->render($this->rootView, [
            'page' => $page,
        ]);

        return new Response($html);
    }
}
