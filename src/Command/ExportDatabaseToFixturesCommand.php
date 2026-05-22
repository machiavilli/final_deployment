<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:fixtures:export-from-db',
    description: 'Export current database rows into JSON for DataFixtures generation',
)]
class ExportDatabaseToFixturesCommand extends Command
{
    private const TABLES = [
        'user' => '`user`',
        'category' => 'category',
        'product' => 'product',
        'customer' => 'customer',
        'order' => '`order`',
        'cart' => 'cart',
        'cart_item' => 'cart_item',
        'activity_log' => 'activity_log',
        'app_notification' => 'app_notification',
    ];

    private readonly string $projectDir;

    public function __construct(
        private readonly Connection $connection,
        KernelInterface $kernel,
    ) {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $export = [];

        foreach (self::TABLES as $key => $table) {
            $rows = $this->connection->fetchAllAssociative(sprintf('SELECT * FROM %s ORDER BY id', $table));
            $export[$key] = $rows;
            $io->writeln(sprintf('<info>%s</info>: %d row(s)', $key, \count($rows)));
        }

        $dir = $this->projectDir . '/var/fixture-export';
        (new Filesystem())->mkdir($dir);

        $path = $dir . '/database-export.json';
        file_put_contents(
            $path,
            json_encode($export, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES)
        );

        $io->success(sprintf('Exported to %s', $path));

        return Command::SUCCESS;
    }
}
