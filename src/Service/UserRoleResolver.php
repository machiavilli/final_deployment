<?php

namespace App\Service;

use App\Entity\User;

/**
 * Centralizes who gets staff vs customer roles (ROLE_USER only for storefront customers).
 */
class UserRoleResolver
{
    /** @var list<string> */
    private readonly array $staffAllowedEmails;

    /**
     * @param list<string> $googleStaffAllowedEmails
     */
    public function __construct(array $googleStaffAllowedEmails = [])
    {
        $this->staffAllowedEmails = array_values(array_filter(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            $googleStaffAllowedEmails
        )));
    }

    public function isStaffEmail(?string $email): bool
    {
        if ($email === null || $email === '' || $this->staffAllowedEmails === []) {
            return false;
        }

        return \in_array(strtolower($email), $this->staffAllowedEmails, true);
    }

    /** Customer accounts: storefront access only. */
    public function applyCustomerRoles(User $user): void
    {
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return;
        }

        $user->setRoles(['ROLE_USER']);
    }

    public function applyStaffRoles(User $user): void
    {
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $stored = array_values(array_filter(
                $user->getRoles(),
                static fn (string $role): bool => 'ROLE_USER' !== $role
            ));
            if (!\in_array('ROLE_STAFF', $stored, true)) {
                $stored[] = 'ROLE_STAFF';
            }
            $user->setRoles($stored);

            return;
        }

        $user->setRoles(['ROLE_STAFF']);
    }

    /**
     * Ensures stored roles match email: only allowlisted staff emails keep ROLE_STAFF.
     */
    public function normalizeUserRoles(User $user): void
    {
        if ($this->isStaffEmail($user->getEmail())) {
            $this->applyStaffRoles($user);

            return;
        }

        $this->applyCustomerRoles($user);
    }

    public function isStaffPortalUser(User $user): bool
    {
        return \in_array('ROLE_STAFF', $user->getRoles(), true)
            || \in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
