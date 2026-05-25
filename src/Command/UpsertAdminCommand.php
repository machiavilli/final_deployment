<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:upsert-admin',
    description: 'Create or update an admin user (password and roles) by email',
)]
class UpsertAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Admin email')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Admin username', 'admin')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Admin full name', 'Admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower($this->trimCredential((string) $input->getOption('email')));
        $password = $this->trimCredential((string) $input->getOption('password'));
        $username = $this->trimCredential((string) $input->getOption('username'));
        $name = $this->trimCredential((string) $input->getOption('name'));

        if ($email === '' || $password === '') {
            $io->error('Both --email and --password are required.');

            return Command::FAILURE;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $user->setRoles(['ROLE_ADMIN']);
            $user->setIsActive(true);
            $user->setIsVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $io->note(sprintf('Updated existing user: %s', $email));
        } else {
            $usernameTaken = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($usernameTaken instanceof User) {
                $username = $this->uniqueUsername($username);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setName($name);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setIsActive(true);
            $user->setIsVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $this->entityManager->persist($user);
            $io->note(sprintf('Created new admin: %s', $email));
        }

        $this->entityManager->flush();

        $io->success(sprintf('Admin ready: %s (username: %s)', $email, $user->getUsername()));

        return Command::SUCCESS;
    }

    private function uniqueUsername(string $base): string
    {
        $candidate = $base;
        $suffix = 1;

        while ($this->entityManager->getRepository(User::class)->findOneBy(['username' => $candidate]) instanceof User) {
            $candidate = $base.'_'.$suffix;
            ++$suffix;
        }

        return $candidate;
    }

    private function trimCredential(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B\"'");
    }
}
