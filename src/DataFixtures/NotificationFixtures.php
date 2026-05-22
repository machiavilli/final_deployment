<?php

namespace App\DataFixtures;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class NotificationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"user_id":154,"title":"Order placed","message":"Your order MVLLI-20260522-ECDC1D has been placed successfully. Total: ₱1,575.00","type":"order_placed","order_id":null,"order_number":"MVLLI-20260522-ECDC1D","is_read":false,"created_at":"2026-05-22 03:40:42"},{"user_id":154,"title":"Order status updated","message":"Order MVLLI-20260522-ECDC1D status changed from Pending to Delivered.","type":"order_status","order_id":64,"order_number":"MVLLI-20260522-ECDC1D","is_read":false,"created_at":"2026-05-22 03:42:02"},{"user_id":154,"title":"Payment status updated","message":"Order MVLLI-20260522-ECDC1D payment changed from Pending to Paid.","type":"payment_status","order_id":64,"order_number":"MVLLI-20260522-ECDC1D","is_read":false,"created_at":"2026-05-22 03:42:02"}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            if (!$this->hasReference('user_' . $row['user_id'], User::class)) {
                continue;
            }

            $notification = new Notification();
            $notification->setUser($this->getReference('user_' . $row['user_id'], User::class));
            $notification->setTitle($row['title']);
            $notification->setMessage($row['message']);
            $notification->setType($row['type']);
            $notification->setOrderId($row['order_id'] !== null ? (int) $row['order_id'] : null);
            $notification->setOrderNumber($row['order_number']);
            $notification->setIsRead($row['is_read']);
            $notification->setCreatedAt(new \DateTime($row['created_at']));

            $manager->persist($notification);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, OrderFixtures::class];
    }
}