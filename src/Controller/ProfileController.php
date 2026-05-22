<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Service\ActivityLogService;
use App\Service\UserRoleResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile_show', methods: ['GET'])]
    public function show(UserRoleResolver $userRoleResolver, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $userRoleResolver->normalizeUserRoles($user);
            $entityManager->flush();
        }

        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ActivityLogService $logService
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            
            // Validate current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->render('profile/change_password.html.twig', [
                    'form' => $form,
                ]);
            }

            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $entityManager->flush();

            $logService->logUpdate(
                $user,
                'User',
                $user->getId(),
                ['action' => 'password_change']
            );

            $this->addFlash('success', 'Password changed successfully.');

            return $this->redirectToRoute('app_profile_show');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form,
        ]);
    }
}

