<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Repository\PositionRepository;
use App\Service\PositionAggregatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/positions')]
class PositionApiController extends AbstractController
{
    /**
     * Endpoint to create a position exported from external system (e.g. Odoo).
     */
    #[Route('/external', name: 'api_position_create_external', methods: ['POST', 'OPTIONS'], priority: 10)]
    public function createFromExternal(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'Position title is required.',
            ], Response::HTTP_BAD_REQUEST, [
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $position = new Position();
        $position->setTitle($title);
        $position->setCompany(trim((string)($data['company'] ?? '')) ?: null);
        $position->setLevel(trim((string)($data['level'] ?? '')) ?: null);
        $position->setShortDescription(trim((string)($data['shortDescription'] ?? $data['description'] ?? '')));
        $position->setIsPublic((bool)($data['isPublic'] ?? true));

        $position->setMaxProjects(isset($data['maxProjects']) ? (int)$data['maxProjects'] : 3);

        // Tags
        $tags = $data['projectTags'] ?? [];
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }
        if (is_array($tags)) {
            $position->setProjectTags(array_values(array_filter($tags)));
        }

        // Attributes
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $attrIdent) {
                $attr = is_numeric($attrIdent)
                    ? $em->getRepository(\App\Entity\Attribute::class)->find((int)$attrIdent)
                    : $em->getRepository(\App\Entity\Attribute::class)->findOneBy(['name' => (string)$attrIdent]);
                if ($attr) {
                    $position->addAttribute($attr);
                }
            }
        }

        // Access Rules
        if (isset($data['accessRules']) && is_array($data['accessRules'])) {
            foreach ($data['accessRules'] as $ruleData) {
                $attrIdent = $ruleData['attributeId'] ?? $ruleData['attribute'] ?? null;
                $attr = is_numeric($attrIdent)
                    ? $em->getRepository(\App\Entity\Attribute::class)->find((int)$attrIdent)
                    : $em->getRepository(\App\Entity\Attribute::class)->findOneBy(['name' => (string)$attrIdent]);
                if ($attr && !empty($ruleData['operator']) && isset($ruleData['value'])) {
                    $rule = new \App\Entity\PositionAccessRule();
                    $rule->setPosition($position);
                    $rule->setAttribute($attr);
                    $rule->setOperator((string)$ruleData['operator']);
                    $rule->setValue((string)$ruleData['value']);
                    $em->persist($rule);
                }
            }
        }

        // Generate unique API token
        $position->ensureApiToken();

        $em->persist($position);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Position created successfully in Course Project.',
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'company' => $position->getCompany(),
                'level' => $position->getLevel(),
                'apiToken' => $position->getApiToken(),
                'isPublic' => $position->isPublic(),
            ],
        ], Response::HTTP_CREATED, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    /**
     * Endpoint to delete a position requested by external system (e.g. Odoo).
     */
    #[Route('/external/delete', name: 'api_position_delete_external', methods: ['POST', 'DELETE', 'OPTIONS'], priority: 10)]
    public function deleteFromExternal(
        Request $request,
        PositionRepository $positionRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $id = $data['id'] ?? $data['external_id'] ?? null;
        $token = $data['apiToken'] ?? $data['api_token'] ?? null;

        $position = null;
        if ($id) {
            $position = $positionRepo->find((int)$id);
        }
        if (!$position && $token) {
            $position = $positionRepo->findOneBy(['apiToken' => trim((string)$token)]);
        }

        if (!$position) {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Position not found in Course Project.',
            ], Response::HTTP_NOT_FOUND, [
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $deletedId = $position->getId();
        $em->remove($position);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => "Position #{$deletedId} deleted successfully in Course Project.",
            'deletedId' => $deletedId,
        ], Response::HTTP_OK, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Endpoint to update a position requested by external system (e.g. Odoo).
     */
    #[Route('/external/update', name: 'api_position_update_external', methods: ['POST', 'PUT', 'PATCH', 'OPTIONS'], priority: 10)]
    public function updateFromExternal(
        Request $request,
        PositionRepository $positionRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, PUT, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $id = $data['id'] ?? $data['external_id'] ?? null;
        $token = $data['apiToken'] ?? $data['api_token'] ?? null;

        $position = null;
        if ($id) {
            $position = $positionRepo->find((int)$id);
        }
        if (!$position && $token) {
            $position = $positionRepo->findOneBy(['apiToken' => trim((string)$token)]);
        }

        if (!$position) {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Position not found in Course Project.',
            ], Response::HTTP_NOT_FOUND, [
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        if (array_key_exists('isPublic', $data)) {
            $position->setIsPublic((bool)$data['isPublic']);
        }
        if (array_key_exists('title', $data) && trim((string)$data['title']) !== '') {
            $position->setTitle(trim((string)$data['title']));
        }
        if (array_key_exists('company', $data)) {
            $position->setCompany(trim((string)$data['company']) ?: null);
        }
        if (array_key_exists('level', $data)) {
            $position->setLevel(trim((string)$data['level']) ?: null);
        }
        if (array_key_exists('shortDescription', $data)) {
            $position->setShortDescription(trim((string)$data['shortDescription']));
        }
        if (array_key_exists('maxProjects', $data)) {
            $position->setMaxProjects((int)$data['maxProjects']);
        }
        if (array_key_exists('projectTags', $data)) {
            $tags = $data['projectTags'];
            if (is_string($tags)) {
                $tags = array_map('trim', explode(',', $tags));
            }
            if (is_array($tags)) {
                $position->setProjectTags(array_values(array_filter($tags)));
            }
        }

        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => "Position #{$position->getId()} updated successfully in Course Project.",
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'isPublic' => $position->isPublic(),
                'apiToken' => $position->getApiToken(),
            ],
        ], Response::HTTP_OK, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Endpoint to list all available candidate attributes.
     */
    #[Route('/attributes', name: 'api_position_attributes_list', methods: ['GET', 'OPTIONS'], priority: 10)]
    public function getAttributes(
        Request $request,
        \App\Repository\AttributeRepository $attrRepo
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);
        }

        $attributes = array_map(fn(\App\Entity\Attribute $a) => [
            'id' => $a->getId(),
            'name' => $a->getName(),
            'type' => $a->getType(),
        ], $attrRepo->findAll());

        return $this->json($attributes, Response::HTTP_OK, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }

    /**
     * Public API endpoint accessible with an API token.
     * Returns aggregated results for the specific position identified by token.
     */
    #[Route('/{token}/aggregated', name: 'api_position_aggregated_by_token', methods: ['GET'])]
    public function getAggregatedByPath(
        string $token,
        PositionRepository $positionRepo,
        PositionAggregatorService $aggregator
    ): JsonResponse {
        return $this->resolveAggregatedResponse($token, $positionRepo, $aggregator);
    }

    /**
     * Alternative query-param syntax: /api/positions/aggregated?token=...
     */
    #[Route('/aggregated', name: 'api_position_aggregated_by_query', methods: ['GET'], priority: 2)]
    public function getAggregatedByQuery(
        Request $request,
        PositionRepository $positionRepo,
        PositionAggregatorService $aggregator
    ): JsonResponse {
        $token = trim((string) $request->query->get('token', ''));
        return $this->resolveAggregatedResponse($token, $positionRepo, $aggregator);
    }

    private function resolveAggregatedResponse(
        string $token,
        PositionRepository $positionRepo,
        PositionAggregatorService $aggregator
    ): JsonResponse {
        if ($token === '') {
            return $this->json([
                'status' => 'error',
                'message' => 'API token is required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $position = $positionRepo->findOneBy(['apiToken' => $token]);

        if (!$position) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or unrecognized API token. No matching position found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $aggregator->getAggregatedData($position);

        return $this->json($data, Response::HTTP_OK, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        ]);
    }
}
