<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CartFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"cart_1","user_id":144,"total":2080,"created_at":"2026-04-30 09:20:20","updated_at":"2026-05-21 22:16:40"},{"ref":"cart_2","user_id":154,"total":0,"created_at":"2026-05-21 19:13:42","updated_at":"2026-05-22 03:40:41"}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            if (!$this->hasReference('user_' . $row['user_id'], User::class)) {
                continue;
            }

            $user = $this->getReference('user_' . $row['user_id'], User::class);
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setTotal($row['total']);
            $cart->setCreatedAt(new \DateTime($row['created_at']));
            if ($row['updated_at'] !== null) {
                $cart->setUpdatedAt(new \DateTime($row['updated_at']));
            }
            $user->setCart($cart);

            $manager->persist($cart);
            $this->addReference($row['ref'], $cart);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}