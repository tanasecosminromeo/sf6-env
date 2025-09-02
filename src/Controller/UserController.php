<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

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
}