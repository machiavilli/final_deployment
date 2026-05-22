<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mobile/me')]
#[IsGranted('ROLE_USER')]
class MobileProfileController extends AbstractController
{
    #[Route('', name: 'api_mobile_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $response = ApiResponse::success('Profile retrieved', [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);

        return $this->json($response->toArray());
    }
}
