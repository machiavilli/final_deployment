<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:fixtures:generate-from-export',
    description: 'Generate DataFixtures PHP files from var/fixture-export/database-export.json',
)]
class GenerateFixturesFromExportCommand extends Command
{
    private readonly string $projectDir;

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        require $this->projectDir . '/tools/DatabaseFixtureExporter.php';
        (new \DatabaseFixtureExporter($this->projectDir))->generate();

        $io->success('Fixture files written to src/DataFixtures/');

        return Command::SUCCESS;
    }
}
