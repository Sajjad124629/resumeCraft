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
        private \Symfony\Bundle\SecurityBundle\Security $security,
        private string $rootView = 'app.html.twig',
        private array $sharedProps = []
    ) {}

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

        $user = $this->security->getUser();
        $authProp = null;
        if ($user instanceof \App\Entity\User) {
            $userDetails = $user->getUserDetails();
            $fullname = $userDetails ? ($userDetails->getFirstName() . ' ' . $userDetails->getLastName()) : ($user->getRole() ? $user->getRole()->getName() : 'Admin');
            $photo = $userDetails?->getPhoto();
            $roleSlug = $user->getRole()?->getSlug() ?? 'ROLE_USER';
            $roleName = $user->getRole()?->getName() ?? 'User';
            $candidateProfile = $user->getCandidateProfile();
            $candidateProfileId = $candidateProfile ? $candidateProfile->getId() : null;

            $authProp = [
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $fullname,
                    'fullName' => $fullname,
                    'photo' => $photo,
                    'avatar' => $photo,
                    'roles' => $user->getRoles(),
                    'role' => $roleSlug,
                    'roleName' => $roleName,
                    'candidateProfileId' => $candidateProfileId,
                    'user_detail' => [
                        'fullname' => $fullname,
                        'image' => $photo,
                    ],
                ],
            ];
        }

        $locale = $request->getLocale();
        $translations = $this->getTranslations($locale);

        $flashProp = [];
        if ($request && $request->hasSession()) {
            $session = $request->getSession();
            if ($session instanceof \Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface) {
                $flashBag = $session->getFlashBag();
                $allFlashes = $flashBag->all();
                if (!empty($allFlashes)) {
                    foreach ($allFlashes as $type => $messages) {
                        $msg = is_array($messages) ? implode("\n", $messages) : (string)$messages;
                        $alertType = match ($type) {
                            'error', 'danger' => 'error',
                            'success' => 'success',
                            'warning' => 'warning',
                            default => 'info',
                        };
                        $flashProp = [
                            'message' => $msg,
                            'alertType' => $alertType,
                            'all' => $allFlashes,
                        ];
                        break;
                    }
                }
            }
        }

        $defaultProps = [
            'routes' => $this->getRoutes(),
            'settings' => [
                'title' => 'ResumeCraft',
                'logo' => null,
            ],
            'auth' => $authProp,
            'flash' => $flashProp,
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'image' => 'language/EN.svg'],
                ['code' => 'es', 'name' => 'Spanish', 'image' => 'language/ES.svg'],
            ],
            'locale' => $locale,
            'translations' => $translations,
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
            $response = new JsonResponse($page, 200, [
                'X-Inertia' => 'true',
                'Vary' => 'X-Inertia',
            ]);
            $response->headers->set('Cache-Control', 'no-cache, private');
            return $response;
        }

        $html = $this->twig->render($this->rootView, [
            'page' => $page,
        ]);

        $response = new Response($html);
        $response->headers->set('Vary', 'X-Inertia');
        return $response;
    }

    private function getTranslations(string $locale): array
    {
        $en = [
            // Navigation / Menus
            'Dashboard' => 'Dashboard',
            'Positions' => 'Positions',
            'Attribute Library' => 'Attribute Library',
            'Attributes' => 'Attributes',
            'My Profile' => 'My Profile',
            'Profile' => 'Profile',
            'Candidate Profile' => 'Candidate Profile',
            'My Candidate Profile' => 'My Candidate Profile',
            'User Management' => 'User Management',
            'Logout' => 'Logout',
            'Sign Out' => 'Sign Out',
            'Log In' => 'Log In',
            'Sign Up' => 'Sign Up',
            'Search' => 'Search',
            'Search positions, CVs...' => 'Search positions, CVs...',
            'Search...' => 'Search...',
            'Admin' => 'Admin',
            'Recruiter' => 'Recruiter',
            'Candidate' => 'Candidate',
            'Admin Mode' => 'Admin Mode',

            // Dashboard
            'Total Candidates' => 'Total Candidates',
            'Total Positions' => 'Total Positions',
            'Total CVs' => 'Total CVs',
            'New CVs (24h)' => 'New CVs (24h)',
            'Latest Positions' => 'Latest Positions',
            'Most Popular Positions' => 'Most Popular Positions',
            'Technology Tag Cloud' => 'Technology Tag Cloud',
            'CVs Submitted' => 'CVs Submitted',
            'No tags available yet. Add projects to your profile!' => 'No tags available yet. Add projects to your profile!',

            // Buttons / Actions
            'Save' => 'Save',
            'Cancel' => 'Cancel',
            'Delete' => 'Delete',
            'Edit' => 'Edit',
            'Submit' => 'Submit',
            'Create' => 'Create',
            'Create Position' => 'Create Position',
            'Create Attribute' => 'Create Attribute',
            'Add Attribute' => 'Add Attribute',
            'Add Project' => 'Add Project',
            'Add Rule' => 'Add Rule',
            'Add to Profile' => 'Add to Profile',
            'Duplicate' => 'Duplicate',
            'View' => 'View',
            'Back' => 'Back',
            'Back to Users' => 'Back to Users',
            'View Public Profile' => 'View Public Profile',
            'View / Edit CV' => 'View / Edit CV',
            'Browse Positions to Apply' => 'Browse Positions to Apply',
            'Generate CV' => 'Generate CV',
            'Publish' => 'Publish',
            'Unpublish' => 'Unpublish',
            'Download PDF' => 'Download PDF',
            'Export' => 'Export',
            'Block' => 'Block',
            'Unblock' => 'Unblock',
            'Set Role' => 'Set Role',
            'Open Profile' => 'Open Profile',
            'Saving...' => 'Saving...',
            'Saved' => 'Saved',
            'Creating...' => 'Creating...',

            // Statuses
            'Status' => 'Status',
            'Active' => 'Active',
            'Blocked' => 'Blocked',
            'Published' => 'Published',
            'Draft' => 'Draft',
            'Pending' => 'Pending',
            'Approved' => 'Approved',
            'Rejected' => 'Rejected',
            'Public' => 'Public',
            'Restricted' => 'Restricted',
            'Confidential' => 'Confidential',
            'Verified' => 'Verified',
            'Yes' => 'Yes',
            'No' => 'No',
            'None' => 'None',
            'Present' => 'Present',
            'Not set' => 'Not set',

            // Form Labels
            'First Name' => 'First Name',
            'Last Name' => 'Last Name',
            'Location' => 'Location',
            'Personal Photo' => 'Personal Photo',
            'Title' => 'Title',
            'Company' => 'Company',
            'Level' => 'Level',
            'Description' => 'Description',
            'Short Description' => 'Short Description',
            'Max Projects Allowed' => 'Max Projects Allowed',
            'Is Public' => 'Is Public',
            'Public?' => 'Public?',
            'Position Title' => 'Position Title',
            'Company Name' => 'Company Name',
            'Relevant Project Technology Tags' => 'Relevant Project Technology Tags',
            'Required Attributes' => 'Required Attributes',
            'Access Rules' => 'Access Rules',
            'Attribute' => 'Attribute',
            'Operator' => 'Operator',
            'Value' => 'Value',
            'Select Attribute' => 'Select Attribute',
            'Name' => 'Name',
            'Category' => 'Category',
            'Type' => 'Type',
            'Email' => 'Email',
            'Password' => 'Password',
            'Confirm Password' => 'Confirm Password',
            'Role' => 'Role',
            'User' => 'User',
            'Profile Link' => 'Profile Link',
            'Numeric' => 'Numeric',
            'String' => 'String',
            'Date' => 'Date',
            'Period' => 'Period',
            'Boolean' => 'Boolean',
            'Start Date' => 'Start Date',
            'End Date' => 'End Date',
            'Select option' => 'Select option',
            'Lookup by Prefix' => 'Lookup by Prefix',
            'Filter by Category' => 'Filter by Category',
            'All Categories' => 'All Categories',
            'Recently Used' => 'Recently Used',
            'Matching Attributes' => 'Matching Attributes',
            'Add Attribute from Library' => 'Add Attribute from Library',
            'The field labels marked with * are required input fields.' => 'The field labels marked with * are required input fields.',

            // Table / Lists
            'selected' => 'selected',
            'CVs' => 'CVs',
            'Likes' => 'Likes',
            'Created' => 'Created',
            'No positions found.' => 'No positions found.',
            'No attributes found.' => 'No attributes found.',
            'No users found.' => 'No users found.',
            'No CVs created yet. Explore available positions to generate a tailored CV!' => 'No CVs created yet. Explore available positions to generate a tailored CV!',
            'No matching unadded attributes found.' => 'No matching unadded attributes found.',
            "You haven't added any projects yet." => "You haven't added any projects yet.",
            'No attributes added yet. Click "Add Attribute" to select from the library!' => 'No attributes added yet. Click "Add Attribute" to select from the library!',
        ];

        $es = [
            // Navigation / Menus
            'Dashboard' => 'Tablero',
            'Positions' => 'Posiciones',
            'Attribute Library' => 'Biblioteca de Atributos',
            'Attributes' => 'Atributos',
            'My Profile' => 'Mi Perfil',
            'Profile' => 'Perfil',
            'Candidate Profile' => 'Perfil de Candidato',
            'My Candidate Profile' => 'Mi Perfil de Candidato',
            'User Management' => 'Gestión de Usuarios',
            'Logout' => 'Cerrar sesión',
            'Sign Out' => 'Cerrar sesión',
            'Log In' => 'Iniciar Sesión',
            'Sign Up' => 'Registrarse',
            'Search' => 'Buscar',
            'Search positions, CVs...' => 'Buscar posiciones, CVs...',
            'Search...' => 'Buscar...',
            'Admin' => 'Administrador',
            'Recruiter' => 'Reclutador',
            'Candidate' => 'Candidato',
            'Admin Mode' => 'Modo Administrador',

            // Dashboard
            'Total Candidates' => 'Total de Candidatos',
            'Total Positions' => 'Total de Posiciones',
            'Total CVs' => 'Total de CVs',
            'New CVs (24h)' => 'Nuevos CVs (24h)',
            'Latest Positions' => 'Últimas Posiciones',
            'Most Popular Positions' => 'Posiciones Más Populares',
            'Technology Tag Cloud' => 'Nube de Etiquetas de Tecnología',
            'CVs Submitted' => 'CVs Enviados',
            'No tags available yet. Add projects to your profile!' => '¡Aún no hay etiquetas disponibles. Agrega proyectos a tu perfil!',

            // Buttons / Actions
            'Save' => 'Guardar',
            'Cancel' => 'Cancelar',
            'Delete' => 'Eliminar',
            'Edit' => 'Editar',
            'Submit' => 'Enviar',
            'Create' => 'Crear',
            'Create Position' => 'Crear Posición',
            'Create Attribute' => 'Crear Atributo',
            'Add Attribute' => 'Agregar Atributo',
            'Add Project' => 'Agregar Proyecto',
            'Add Rule' => 'Agregar Regla',
            'Add to Profile' => 'Agregar al Perfil',
            'Duplicate' => 'Duplicar',
            'View' => 'Ver',
            'Back' => 'Atrás',
            'Back to Users' => 'Volver a Usuarios',
            'View Public Profile' => 'Ver Perfil Público',
            'View / Edit CV' => 'Ver / Editar CV',
            'Browse Positions to Apply' => 'Explorar Posiciones para Postular',
            'Generate CV' => 'Generar CV',
            'Publish' => 'Publicar',
            'Unpublish' => 'Despublicar',
            'Download PDF' => 'Descargar PDF',
            'Export' => 'Exportar',
            'Block' => 'Bloquear',
            'Unblock' => 'Desbloquear',
            'Set Role' => 'Asignar Rol',
            'Open Profile' => 'Abrir Perfil',
            'Saving...' => 'Guardando...',
            'Saved' => 'Guardado',
            'Creating...' => 'Creando...',

            // Statuses
            'Status' => 'Estado',
            'Active' => 'Activo',
            'Blocked' => 'Bloqueado',
            'Published' => 'Publicado',
            'Draft' => 'Borrador',
            'Pending' => 'Pendiente',
            'Approved' => 'Aprobado',
            'Rejected' => 'Rechazado',
            'Public' => 'Público',
            'Restricted' => 'Restringido',
            'Confidential' => 'Confidencial',
            'Verified' => 'Verificado',
            'Yes' => 'Sí',
            'No' => 'No',
            'None' => 'Ninguno',
            'Present' => 'Presente',
            'Not set' => 'No establecido',

            // Form Labels
            'First Name' => 'Nombre',
            'Last Name' => 'Apellido',
            'Location' => 'Ubicación',
            'Personal Photo' => 'Foto Personal',
            'Title' => 'Título',
            'Company' => 'Empresa',
            'Level' => 'Nivel',
            'Description' => 'Descripción',
            'Short Description' => 'Descripción Corta',
            'Max Projects Allowed' => 'Máximo de Proyectos Permitidos',
            'Is Public' => 'Es Público',
            'Public?' => '¿Público?',
            'Position Title' => 'Título de la Posición',
            'Company Name' => 'Nombre de la Empresa',
            'Relevant Project Technology Tags' => 'Etiquetas de Tecnología Relevantes',
            'Required Attributes' => 'Atributos Requeridos',
            'Access Rules' => 'Reglas de Acceso',
            'Attribute' => 'Atributo',
            'Operator' => 'Operador',
            'Value' => 'Valor',
            'Select Attribute' => 'Seleccionar Atributo',
            'Name' => 'Nombre',
            'Category' => 'Categoría',
            'Type' => 'Tipo',
            'Email' => 'Correo Electrónico',
            'Password' => 'Contraseña',
            'Confirm Password' => 'Confirmar Contraseña',
            'Role' => 'Rol',
            'User' => 'Usuario',
            'Profile Link' => 'Enlace del Perfil',
            'Numeric' => 'Numérico',
            'String' => 'Texto',
            'Date' => 'Fecha',
            'Period' => 'Período',
            'Boolean' => 'Booleano',
            'Start Date' => 'Fecha de Inicio',
            'End Date' => 'Fecha de Finalización',
            'Select option' => 'Seleccionar opción',
            'Lookup by Prefix' => 'Buscar por Prefijo',
            'Filter by Category' => 'Filtrar por Categoría',
            'All Categories' => 'Todas las Categorías',
            'Recently Used' => 'Usados Recientemente',
            'Matching Attributes' => 'Atributos Coincidentes',
            'Add Attribute from Library' => 'Agregar Atributo de la Biblioteca',
            'The field labels marked with * are required input fields.' => 'Los campos marcados con * son campos obligatorios.',

            // Table / Lists
            'selected' => 'seleccionados',
            'CVs' => 'CVs',
            'Likes' => 'Me gusta',
            'Created' => 'Creado',
            'No positions found.' => 'No se encontraron posiciones.',
            'No attributes found.' => 'No se encontraron atributos.',
            'No users found.' => 'No se encontraron usuarios.',
            'No CVs created yet. Explore available positions to generate a tailored CV!' => '¡Aún no hay CVs creados. Explora posiciones disponibles para generar un CV a medida!',
            'No matching unadded attributes found.' => 'No se encontraron atributos no agregados coincidentes.',
            "You haven't added any projects yet." => 'Aún no has agregado ningún proyecto.',
            'No attributes added yet. Click "Add Attribute" to select from the library!' => '¡Aún no hay atributos agregados. Haz clic en "Agregar Atributo" para seleccionar de la biblioteca!',
        ];

        return $locale === 'es' ? $es : $en;
    }
}
