<?php

namespace App\Controller;

use App\Entity\Attribute;
use App\Entity\AttributeCategory;
use App\Service\InertiaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/attributes')]
#[IsGranted('ROLE_RECRUITER')]
class AttributeController extends AbstractController
{
    #[Route('', name: 'app_attribute_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $search = trim($request->query->get('search', ''));
        $sort = $request->query->get('sort', 'name');
        $dir = strtolower($request->query->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $qb = $em->getRepository(Attribute::class)->createQueryBuilder('a')
            ->leftJoin('a.category', 'c');

        if ($search !== '') {
            $qb->andWhere('LOWER(a.name) LIKE :q OR LOWER(a.description) LIKE :q OR LOWER(a.type) LIKE :q OR LOWER(c.name) LIKE :q')
                ->setParameter('q', '%' . strtolower($search) . '%');
        }

        $countQb = clone $qb;
        $totalRows = (int) $countQb->select('count(a.id)')->getQuery()->getSingleScalarResult();

        $allowedSorts = ['name', 'type', 'description', 'id'];
        $sortCol = in_array($sort, $allowedSorts) ? 'a.' . $sort : 'a.name';
        if ($sort === 'category') {
            $sortCol = 'c.name';
        }

        $qb->select('a')
            ->orderBy($sortCol, $dir)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $attributes = $qb->getQuery()->getResult();
        $categories = $em->getRepository(AttributeCategory::class)->findAll();

        $attributeArray = array_map(function (Attribute $attr) {
            return [
                'id' => $attr->getId(),
                'name' => $attr->getName(),
                'description' => $attr->getDescription(),
                'type' => $attr->getType(),
                'options' => $attr->getOptions(),
                'category' => [
                    'id' => $attr->getCategory()?->getId(),
                    'name' => $attr->getCategory()?->getName(),
                ]
            ];
        }, $attributes);

        $categoryArray = array_map(function (AttributeCategory $cat) {
            return [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
            ];
        }, $categories);

        return $inertia->render('attributes/Index', [
            'attributes' => $attributeArray,
            'totalRows' => $totalRows,
            'currentPage' => $page,
            'pageSize' => $limit,
            'search' => $search,
            'sort' => $sort,
            'sortDir' => $dir,
            'categories' => $categoryArray,
        ]);
    }

    #[Route('/create', name: 'app_attribute_create_view', methods: ['GET'])]
    public function createView(EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $categories = $em->getRepository(AttributeCategory::class)->findAll();
        $categoryArray = array_map(fn($cat) => ['id' => $cat->getId(), 'name' => $cat->getName()], $categories);

        return $inertia->render('attributes/Create', [
            'categories' => $categoryArray,
        ]);
    }

    #[Route('/create', name: 'app_attribute_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->getPayload()->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?: $request->request->all();
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->addFlash('error', 'Attribute name is required.');
            return $this->redirectToRoute('app_attribute_create_view');
        }

        $existing = $em->getRepository(Attribute::class)->findOneBy(['name' => $name]);
        if ($existing) {
            $this->addFlash('error', "An attribute named '{$name}' already exists.");
            return $this->redirectToRoute('app_attribute_create_view');
        }

        $categoryId = $data['category_id'] ?? null;
        $category = $categoryId ? $em->getRepository(AttributeCategory::class)->find($categoryId) : null;
        if (!$category) {
            $this->addFlash('error', 'Please select a valid category.');
            return $this->redirectToRoute('app_attribute_create_view');
        }

        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setDescription(trim($data['description'] ?? ''));
        $attribute->setType($data['type'] ?? 'string');
        if (isset($data['options']) && is_array($data['options'])) {
            $attribute->setOptions($data['options']);
        }
        $attribute->setCategory($category);

        $em->persist($attribute);
        $em->flush();
        $this->addFlash('success', 'Attribute created successfully.');
        return $this->redirectToRoute('app_attribute_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_attribute_edit_view', methods: ['GET'])]
    public function editView(Attribute $attribute, EntityManagerInterface $em, InertiaService $inertia): Response
    {
        $categories = $em->getRepository(AttributeCategory::class)->findAll();
        $categoryArray = array_map(fn($cat) => ['id' => $cat->getId(), 'name' => $cat->getName()], $categories);

        return $inertia->render('attributes/Edit', [
            'attribute' => [
                'id' => $attribute->getId(),
                'name' => $attribute->getName(),
                'description' => $attribute->getDescription(),
                'type' => $attribute->getType(),
                'options' => $attribute->getOptions() ?? [],
                'category_id' => $attribute->getCategory()?->getId(),
            ],
            'categories' => $categoryArray,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_attribute_edit', methods: ['POST', 'PUT'])]
    #[Route('/{id}', name: 'app_attribute_update', methods: ['POST', 'PUT'])]
    public function edit(Attribute $attribute, Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->getPayload()->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?: $request->request->all();
        }

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                $this->addFlash('error', 'Attribute name cannot be empty.');
                return $this->redirectToRoute('app_attribute_edit_view', ['id' => $attribute->getId()]);
            }

            $existing = $em->getRepository(Attribute::class)->findOneBy(['name' => $name]);
            if ($existing && $existing->getId() !== $attribute->getId()) {
                $this->addFlash('error', "An attribute named '{$name}' already exists.");
                return $this->redirectToRoute('app_attribute_edit_view', ['id' => $attribute->getId()]);
            }
            $attribute->setName($name);
        }

        if (array_key_exists('description', $data)) {
            $attribute->setDescription(trim($data['description'] ?? ''));
        }

        if (!empty($data['type'])) {
            $attribute->setType($data['type']);
        }

        if (isset($data['options'])) {
            $attribute->setOptions(is_array($data['options']) ? $data['options'] : []);
        }

        if (!empty($data['category_id'])) {
            $category = $em->getRepository(AttributeCategory::class)->find($data['category_id']);
            if ($category) {
                $attribute->setCategory($category);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Attribute updated successfully.');
        return $this->redirectToRoute('app_attribute_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_attribute_delete', methods: ['DELETE'])]
    public function delete(Attribute $attribute, EntityManagerInterface $em): Response
    {
        $em->remove($attribute);
        $em->flush();
        $this->addFlash('success', 'Attribute deleted successfully.');
        return $this->redirectToRoute('app_attribute_index', [], Response::HTTP_SEE_OTHER);
    }
}
