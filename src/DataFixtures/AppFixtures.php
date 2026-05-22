<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Ensures admin exists after database snapshot fixtures are loaded.
 */
class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $adminEmail = $_ENV['FIXTURES_ADMIN_EMAIL'] ?? 'admin@gmail.com';
        $existingAdmin = $manager->getRepository(User::class)->findOneBy(['email' => $adminEmail]);

        if (!$existingAdmin) {
            $admin = new User();
            $admin->setEmail($adminEmail);
            $admin->setUsername('mvlli_admin');
            $admin->setName('MVLLI Administrator');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsVerified(true);
            $admin->setPassword($this->passwordHasher->hashPassword(
                $admin,
                $_ENV['FIXTURES_ADMIN_PASSWORD'] ?? 'admin123'
            ));
            $manager->persist($admin);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
            ProductFixtures::class,
            CustomerFixtures::class,
            OrderFixtures::class,
            CartFixtures::class,
            CartItemFixtures::class,
            ActivityLogFixtures::class,
            NotificationFixtures::class,
        ];
    }
}