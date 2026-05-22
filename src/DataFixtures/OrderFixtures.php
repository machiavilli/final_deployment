<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"order_46","product_name":"Barong Tagalog","quantity":2,"price":1200,"status":"completed","order_date":"2026-03-09 10:46:05","customer_id":121,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_47","product_name":"Women\'s Blouse","quantity":3,"price":380,"status":"completed","order_date":"2026-03-14 10:46:05","customer_id":122,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_48","product_name":"Men\'s Jeans","quantity":2,"price":680,"status":"pending","order_date":"2026-03-19 10:46:05","customer_id":123,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_49","product_name":"Fashion Handbag","quantity":1,"price":650,"status":"completed","order_date":"2026-03-21 10:46:05","customer_id":124,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_50","product_name":"Men\'s Polo Shirt","quantity":5,"price":450,"status":"completed","order_date":"2026-03-24 10:46:05","customer_id":125,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_51","product_name":"Filipiniana Dress","quantity":1,"price":1500,"status":"processing","order_date":"2026-03-27 10:46:05","customer_id":126,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_52","product_name":"Women\'s Sandals","quantity":2,"price":380,"status":"completed","order_date":"2026-03-29 10:46:05","customer_id":127,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_53","product_name":"Kids T-Shirt Set","quantity":3,"price":280,"status":"completed","order_date":"2026-03-31 10:46:05","customer_id":128,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_54","product_name":"Men\'s Suit","quantity":1,"price":2500,"status":"pending","order_date":"2026-04-03 10:46:05","customer_id":129,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_55","product_name":"Leather Belt","quantity":4,"price":280,"status":"completed","order_date":"2026-04-05 10:46:05","customer_id":130,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_56","product_name":"Sports Jersey","quantity":2,"price":420,"status":"processing","order_date":"2026-04-06 10:46:05","customer_id":131,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_57","product_name":"Women\'s Dress","quantity":6,"price":550,"status":"completed","order_date":"2026-04-07 10:46:05","customer_id":132,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_58","product_name":"Summer Beach Shirt","quantity":3,"price":350,"status":"pending","order_date":"2026-04-07 22:46:05","customer_id":133,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_59","product_name":"Baseball Cap","quantity":5,"price":180,"status":"completed","order_date":"2026-04-08 04:46:05","customer_id":134,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_60","product_name":"Men\'s Leather Shoes","quantity":1,"price":850,"status":"processing","order_date":"2026-04-08 07:46:05","customer_id":135,"created_by":null,"order_number":null,"payment_method":null,"payment_status":null,"order_source":"manual","shipping_full_name":null,"shipping_phone":null,"shipping_address":null,"shipping_city":null,"shipping_postal_code":null,"order_notes":null},{"ref":"order_61","product_name":"JESSICA Handbag","quantity":1,"price":420,"status":"pending","order_date":"2026-05-21 19:14:17","customer_id":136,"created_by":null,"order_number":"MVLLI-20260521-6A38EB","payment_method":"cod","payment_status":"pending","order_source":"website","shipping_full_name":"juanne","shipping_phone":"9999","shipping_address":"wow wow","shipping_city":"duma","shipping_postal_code":"8700","order_notes":null},{"ref":"order_62","product_name":"Black Denim Barrel Jeans","quantity":1,"price":680,"status":"delivered","order_date":"2026-05-21 23:00:41","customer_id":136,"created_by":null,"order_number":"MVLLI-20260521-AA0047","payment_method":"cod","payment_status":"paid","order_source":"website","shipping_full_name":"juwon@gmail.com","shipping_phone":"6666","shipping_address":"fhd","shipping_city":"dums","shipping_postal_code":"7585","order_notes":null},{"ref":"order_63","product_name":"Coquette Mini Bag","quantity":1,"price":320,"status":"pending","order_date":"2026-05-21 23:54:57","customer_id":136,"created_by":null,"order_number":"MVLLI-20260521-0868BD","payment_method":"cod","payment_status":"pending","order_source":"website","shipping_full_name":"juanne","shipping_phone":"099999","shipping_address":"duma","shipping_city":"duma","shipping_postal_code":"6218","order_notes":null},{"ref":"order_64","product_name":"SHALISA Accessory Set","quantity":1,"price":1575,"status":"delivered","order_date":"2026-05-22 03:40:41","customer_id":136,"created_by":null,"order_number":"MVLLI-20260522-ECDC1D","payment_method":"cod","payment_status":"paid","order_source":"website","shipping_full_name":"juanne","shipping_phone":"53939","shipping_address":"fg","shipping_city":"f6fyf","shipping_postal_code":"5675","order_notes":null}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            $order = new Order();
            $order->setProductName($row['product_name']);
            $order->setQuantity($row['quantity']);
            $order->setPrice($row['price']);
            $order->setStatus($row['status']);
            $order->setOrderDate(new \DateTime($row['order_date']));
            $order->setOrderSource($row['order_source']);

            if ($row['customer_id'] !== null && $this->hasReference('customer_' . $row['customer_id'], Customer::class)) {
                $order->setCustomer($this->getReference('customer_' . $row['customer_id'], Customer::class));
            }
            if ($row['created_by'] !== null && $this->hasReference('user_' . $row['created_by'], User::class)) {
                $order->setCreatedBy($this->getReference('user_' . $row['created_by'], User::class));
            }
            if ($row['order_number'] !== null) {
                $order->setOrderNumber($row['order_number']);
            }
            if ($row['payment_method'] !== null) {
                $order->setPaymentMethod($row['payment_method']);
            }
            if ($row['payment_status'] !== null) {
                $order->setPaymentStatus($row['payment_status']);
            }
            if ($row['shipping_full_name'] !== null) {
                $order->setShippingFullName($row['shipping_full_name']);
            }
            if ($row['shipping_phone'] !== null) {
                $order->setShippingPhone($row['shipping_phone']);
            }
            if ($row['shipping_address'] !== null) {
                $order->setShippingAddress($row['shipping_address']);
            }
            if ($row['shipping_city'] !== null) {
                $order->setShippingCity($row['shipping_city']);
            }
            if ($row['shipping_postal_code'] !== null) {
                $order->setShippingPostalCode($row['shipping_postal_code']);
            }
            if ($row['order_notes'] !== null) {
                $order->setOrderNotes($row['order_notes']);
            }

            $manager->persist($order);
            $this->addReference($row['ref'], $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CustomerFixtures::class, UserFixtures::class];
    }
}