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
    name: 'app:verify-login',
    description: 'Check whether an email/password would authenticate (for deploy debugging)',
)]
class VerifyUserLoginCommand extends Command
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
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password to test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower(trim((string) $input->getOption('email'), " \t\n\r\0\x0B\"'"));
        $password = trim((string) $input->getOption('password'), " \t\n\r\0\x0B\"'");

        if ($email === '' || $password === '') {
            $io->error('Provide --email and --password');

            return Command::FAILURE;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $io->error(sprintf('No user found with email "%s".', $email));
            $io->note('Run: php bin/console app:upsert-admin --email=... --password=...');

            return Command::FAILURE;
        }

        $passwordOk = $this->passwordHasher->isPasswordValid($user, $password);

        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $user->getEmail()],
                ['Username', $user->getUsername()],
                ['Roles (stored)', json_encode($user->getRoles())],
                ['Active', $user->isActive() ? 'yes' : 'no'],
                ['Verified', $user->isVerified() ? 'yes' : 'no'],
                ['Password matches', $passwordOk ? 'yes' : 'no'],
            ],
        );

        if (!$passwordOk) {
            $io->warning('Password does not match. Run app:upsert-admin to reset it.');

            return Command::FAILURE;
        }

        $io->success('Credentials are valid for this database.');

        return Command::SUCCESS;
    }
}
