<?php

namespace App\Security;

use App\Entity\User;
use App\Service\UserRoleResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRoleResolver $userRoleResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->userRoleResolver->normalizeUserRoles($user);
            $this->entityManager->flush();
        }

        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        if ($this->userRoleResolver->isStaffEmail($user->getEmail())
            && $this->userRoleResolver->isStaffPortalUser($user)) {
            return new RedirectResponse($this->urlGenerator->generate('app_staff_home'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
