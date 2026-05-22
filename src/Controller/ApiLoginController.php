<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user, EntityManagerInterface $em): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'missing credentials'], 401);
        }

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $user->setApiToken($token);
        $em->flush();

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ],
            'roles' => $user->getRoles(),
            'token' => $token,
        ]);
    }
}


