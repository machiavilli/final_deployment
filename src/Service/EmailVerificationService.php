<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private readonly string $mailerFromEmail,
        private readonly string $mailerFromName,
        private readonly bool $mailerEnabled,
    ) {}

    /**
     * Generate a unique verification token
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Queue a verification email (async via Messenger). Returns false when mail is disabled.
     */
    public function sendVerificationEmail(User $user, string $verificationUrl): bool
    {
        if (!$this->mailerEnabled) {
            return false;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
            ->to(new Address($user->getEmail()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);

        return true;
    }

    /**
     * Verify a token and mark user as verified
     */
    public function verifyToken(string $token): ?User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Check if a user needs verification
     */
    public function needsVerification(User $user): bool
    {
        return !$user->isVerified();
    }
}
