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
    name: 'app:ensure-initial-admin',
    description: 'Create the first admin user when the database has no users (safe for deploy bootstrap)',
)]
class EnsureInitialAdminCommand extends Command
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

        $userCount = (int) $this->entityManager->getRepository(User::class)->count([]);
        if ($userCount > 0) {
            $io->note(sprintf('Skipping: database already has %d user(s).', $userCount));

            return Command::SUCCESS;
        }

        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');
        $username = (string) $input->getOption('username');
        $name = (string) $input->getOption('name');

        if ($email === '' || $password === '') {
            $io->error('Both --email and --password are required.');

            return Command::FAILURE;
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
        $this->entityManager->flush();

        $io->success(sprintf('Initial admin created: %s (%s)', $username, $email));

        return Command::SUCCESS;
    }
}
