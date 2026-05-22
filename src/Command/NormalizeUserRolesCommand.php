<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\UserRoleResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:users:normalize-roles',
    description: 'Assign ROLE_USER (or staff roles) to all users based on email allowlist',
)]
class NormalizeUserRolesCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserRoleResolver $userRoleResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findAll();
        $updated = 0;

        foreach ($users as $user) {
            $before = $user->getRoles();
            $this->userRoleResolver->normalizeUserRoles($user);
            if ($user->getRoles() !== $before) {
                ++$updated;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Normalized roles for %d of %d users.', $updated, \count($users)));

        return Command::SUCCESS;
    }
}
