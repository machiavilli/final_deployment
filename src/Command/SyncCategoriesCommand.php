<?php

namespace App\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\CategoryCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:categories:sync',
    description: 'Ensure only Tops, Bottoms, Accessories, and Bags categories exist',
)]
class SyncCategoriesCommand extends Command
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $canonical = [];

        foreach (CategoryCatalog::allowedNames() as $name) {
            $category = $this->categoryRepository->findOneBy(['name' => $name]);
            if (!$category) {
                $category = new Category();
                $category->setName($name);
                $this->entityManager->persist($category);
                $io->writeln(sprintf('Created category: %s', $name));
            }
            $canonical[$name] = $category;
        }

        $this->entityManager->flush();

        $movedProducts = 0;
        $removedCategories = 0;

        foreach ($this->categoryRepository->findAll() as $category) {
            $name = $category->getName();
            if ($name === null || CategoryCatalog::isAllowed($name)) {
                continue;
            }

            $targetName = CategoryCatalog::mapLegacyName($name);
            $target = $canonical[$targetName];

            foreach ($category->getProducts() as $product) {
                $product->setCategory($target);
                ++$movedProducts;
            }

            $this->entityManager->remove($category);
            ++$removedCategories;
            $io->writeln(sprintf('Removed "%s" → products moved to "%s"', $name, $targetName));
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Store categories are now: %s. Moved %d product(s), removed %d old categor(ies).',
            implode(', ', CategoryCatalog::allowedNames()),
            $movedProducts,
            $removedCategories
        ));

        return Command::SUCCESS;
    }
}
