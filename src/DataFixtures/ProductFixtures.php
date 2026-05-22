<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"product_130","name":"Lightwash Barrel Jeans","description":"Fit loose for extra comfort.","price":1450,"image":"1e206269-405d-47cc-87fe-b7274eb68d35-6a0f8095ce202.jpg","stock":25,"category_id":86,"created_by":null},{"ref":"product_131","name":"Black Denim Barrel Jeans","description":".","price":680,"image":"30147bc6-704a-4626-ad54-6a8f4a994bbe.jpg","stock":19,"category_id":86,"created_by":null},{"ref":"product_132","name":"Coquette Mini Bag","description":"Casual but so kawaii","price":320,"image":"5af9f60e-a8b4-4fe8-8520-a6c56c745604.jpg","stock":29,"category_id":87,"created_by":null},{"ref":"product_133","name":"Cropped Polo Shirt","description":".","price":500,"image":"67791b3b-09ce-4020-b018-c353fdf90c7c.jpg","stock":8,"category_id":85,"created_by":null},{"ref":"product_134","name":"TALA Charm Bracelets","description":".","price":380,"image":"6eb05b36-5baa-4b0b-9790-2cc316c5d939.jpg","stock":35,"category_id":89,"created_by":null},{"ref":"product_135","name":"Coquette Shoulder Bag","description":".","price":1420,"image":"78c92ab2-e95c-4832-9847-7dbd8244cb8b.jpg","stock":25,"category_id":87,"created_by":null},{"ref":"product_136","name":"Kelly Hand Purse","description":"Made from real Crocodile skin","price":2550,"image":"7d88188c-769c-465d-8218-759683276785.jpg","stock":20,"category_id":87,"created_by":null},{"ref":"product_137","name":"Grey Baggy Pants","description":"Baggy pants for the trendy streetwear","price":780,"image":"827929fd-1ae1-441f-a935-5e9154edbed1.jpg","stock":40,"category_id":86,"created_by":null},{"ref":"product_138","name":"NOVEA Clover Necklace","description":"LUCKY CHARM NECKLACE from NOVEA","price":1880,"image":"987b1c3e-9385-4649-9a3b-539594b3b150.jpg","stock":30,"category_id":89,"created_by":null},{"ref":"product_139","name":"Gold Accessory Set","description":"Simple but Elegant accessories set.","price":5220,"image":"9c6c4cc8-6256-43da-83a5-870a25c0520f.jpg","stock":35,"category_id":89,"created_by":null},{"ref":"product_140","name":"Leather Tote Black","description":"A Comfortable and \\"Handy\\" Black Tote Bag","price":850,"image":"a7b93d63-6853-4f3d-ba27-4f062fd5efb8.jpg","stock":15,"category_id":87,"created_by":null},{"ref":"product_141","name":"Dark Blue Denim Jeans","description":".","price":1380,"image":"cd6f54de-0b23-4291-96f3-a92718bc79bc.jpg","stock":30,"category_id":86,"created_by":null},{"ref":"product_142","name":"Navy Blue Collared Shirt","description":"Women\'s collared shirt with fitted waist","price":450,"image":"dc4a2c7c-85ba-439c-89a6-22178933edec.jpg","stock":25,"category_id":85,"created_by":null},{"ref":"product_143","name":"Winnersquad Red Jersey","description":".","price":880,"image":"f1d7839b-a95a-4665-b8e6-98423af0ebbc.jpg","stock":40,"category_id":89,"created_by":null},{"ref":"product_144","name":"EXCLUSIVE Galinda Set","description":"Accessories inspired from Wicked\'s Galinda","price":11650,"image":"f783cb06-d3d4-4a6a-b173-d6848ac52b2e.jpg","stock":20,"category_id":89,"created_by":null},{"ref":"product_145","name":"SHALISA Accessory Set","description":"SHALISA\'s exclusive accessory set (LIMITED ONLY!)","price":1575,"image":"accessories-69f31b4267230.jpg","stock":49,"category_id":89,"created_by":null},{"ref":"product_146","name":"JESSICA Handbag","description":"perfect for the old money vibe fits.","price":420,"image":"bags.jpg","stock":24,"category_id":87,"created_by":null},{"ref":"product_151","name":"Women\'s Sleepwear Set","description":"Comfortable sleepwear set for women","price":280,"image":"womenssleepwear.avif","stock":30,"category_id":85,"created_by":null}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            $product = new Product();
            $product->setName($row['name']);
            $product->setDescription($row['description']);
            $product->setPrice($row['price']);
            $product->setImage($row['image']);
            $product->setStock($row['stock']);

            if ($row['category_id'] !== null && $this->hasReference('category_' . $row['category_id'], Category::class)) {
                $product->setCategory($this->getReference('category_' . $row['category_id'], Category::class));
            }
            if ($row['created_by'] !== null && $this->hasReference('user_' . $row['created_by'], User::class)) {
                $product->setCreatedBy($this->getReference('user_' . $row['created_by'], User::class));
            }

            $manager->persist($product);
            $this->addReference($row['ref'], $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class, UserFixtures::class];
    }
}