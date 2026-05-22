<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\EmailVerificationService;
use App\Service\UserRoleResolver;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
   public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService,
        UserRoleResolver $userRoleResolver,
    ): Response {

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));


            // Generate verification token
            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            if ($userRoleResolver->isStaffEmail($user->getEmail())) {
                $userRoleResolver->applyStaffRoles($user);
            } else {
                $userRoleResolver->applyCustomerRoles($user);
            }

            $entityManager->persist($user);

            try {
                $entityManager->flush();
            } catch (UniqueConstraintViolationException) {
                $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])
                    ?? $entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()]);

                if ($existing && $existing->getEmail() === $user->getEmail()) {
                    $this->addFlash('error', 'This email is already registered. Try signing in — your account may already exist from a previous attempt.');
                } else {
                    $this->addFlash('error', 'This username is already taken. Please choose another.');
                }

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailQueued = false;
            try {
                $emailQueued = $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            } catch (\Throwable) {
                $emailQueued = false;
            }

            if (!$emailQueued) {
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $entityManager->flush();
                $this->addFlash('success', 'Account created. You can log in now.');
            } else {
                $this->addFlash('success', 'Account created! Check your email to verify your account (you can log in after verifying).');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

