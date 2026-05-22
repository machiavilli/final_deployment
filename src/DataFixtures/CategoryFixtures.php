<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"category_85","ref_slug":"category_tops","name":"Tops","created_by":null},{"ref":"category_86","ref_slug":"category_bottoms","name":"Bottoms","created_by":null},{"ref":"category_87","ref_slug":"category_bags","name":"Bags","created_by":null},{"ref":"category_89","ref_slug":"category_accessories","name":"Accessories","created_by":null}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            $category = new Category();
            $category->setName($row['name']);
            if ($row['created_by'] !== null && $this->hasReference('user_' . $row['created_by'], User::class)) {
                $category->setCreatedBy($this->getReference('user_' . $row['created_by'], User::class));
            }

            $manager->persist($category);
            $this->addReference($row['ref'], $category);
            $this->addReference($row['ref_slug'], $category);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}