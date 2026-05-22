<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CartItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"cart_item_1","cart_id":1,"product_id":141,"quantity":1,"price":380},{"ref":"cart_item_3","cart_id":1,"product_id":140,"quantity":2,"price":850}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            if (
                !$this->hasReference('cart_' . $row['cart_id'], Cart::class)
                || !$this->hasReference('product_' . $row['product_id'], Product::class)
            ) {
                continue;
            }

            $item = new CartItem();
            $item->setCart($this->getReference('cart_' . $row['cart_id'], Cart::class));
            $item->setProduct($this->getReference('product_' . $row['product_id'], Product::class));
            $item->setQuantity($row['quantity']);
            $item->setPrice($row['price']);

            $manager->persist($item);
            $this->addReference($row['ref'], $item);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CartFixtures::class, ProductFixtures::class];
    }
}