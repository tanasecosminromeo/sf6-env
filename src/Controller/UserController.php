<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
final class UserController extends AbstractController
{
    #[Route('/user/{id}', methods: ['GET'], name: 'user_get')]
    #[OA\Tag(name: 'Users')]
    public function showUser(User $user): JsonResponse
    {
        // Fetch the user from the database (omitted for brevity)
        return $this->json([
            'id' => $user->getId(), 
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/user', methods: ['POST'], name: 'user_create')]
    #[OA\Tag(name: 'Users')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'username', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Cosmin Romeo TANASE'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'cosmin@tanase.dev'),
                new OA\Property(property: 'username', type: 'string', example: 'crtanase'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'mysecretpassword2025'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'crtanase'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'cosmin@tanase.dev'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing required fields',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Missing required fields'),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Failed to create user',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Failed to create user'),
            ]
        )
    )]
    public function createUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->request->get('name') || !$request->request->get('email') || !$request->request->get('username') || !$request->request->get('password')) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            $user->setUsername($request->request->get('username'));
            $user->setPassword($request->request->get('password'));
            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Create a new user (omitted for brevity)
        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getUsername(),
            'email' => $user->getEmail(),
        ], Response::HTTP_CREATED);
    }


    #[Route('/user', methods: ['GET'], name: 'user_list')]
    #[OA\Tag(name: 'Users')]
    public function listUsers(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findBy([], null, $limit, $offset);
        $total = $userRepo->count([]);

        $data = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ];
        }, $users);

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'users' => $data,
        ]);
    }
}