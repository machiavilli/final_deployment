<?php

final class DatabaseFixtureExporter
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function generate(): void
    {
        $exportPath = $this->projectDir . '/var/fixture-export/database-export.json';

        if (!is_file($exportPath)) {
            throw new \RuntimeException('Run app:fixtures:export-from-db first (missing database-export.json).');
        }

        /** @var array<string, list<array<string, mixed>>> $data */
        $data = json_decode((string) file_get_contents($exportPath), true, 512, \JSON_THROW_ON_ERROR);

        $fixturesDir = $this->projectDir . '/src/DataFixtures';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0777, true);
        }

        $this->writeUserFixtures($fixturesDir, $data['user'] ?? []);
        $this->writeCategoryFixtures($fixturesDir, $data['category'] ?? []);
        $this->writeProductFixtures($fixturesDir, $data['product'] ?? []);
        $this->writeCustomerFixtures($fixturesDir, $data['customer'] ?? []);
        $this->writeOrderFixtures($fixturesDir, $data['order'] ?? []);
        $this->writeCartFixtures($fixturesDir, $data['cart'] ?? []);
        $this->writeCartItemFixtures($fixturesDir, $data['cart_item'] ?? []);
        $this->writeActivityLogFixtures($fixturesDir, $data['activity_log'] ?? []);
        $this->writeNotificationFixtures($fixturesDir, $data['app_notification'] ?? []);
        $this->writeAppFixtures($fixturesDir);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeUserFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $roles = json_decode((string) ($row['roles'] ?? '[]'), true) ?: [];
            $items[] = [
                'ref' => 'user_' . $row['id'],
                'email' => $row['email'],
                'username' => $row['username'],
                'roles' => $roles,
                'password' => $row['password'],
                'name' => $row['name'],
                'created_at' => $row['created_at'],
                'is_active' => (bool) $row['is_active'],
                'is_verified' => (bool) $row['is_verified'],
                'verification_token' => $row['verification_token'],
                'api_token' => $row['api_token'],
            ];
        }

        $content = <<<'PHP'
<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Loaded from local database export.
 */
class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $rows = %DATA%;

        foreach ($rows as $row) {
            $user = new User();
            $user->setEmail($row['email']);
            $user->setUsername($row['username']);
            $user->setRoles($row['roles']);
            $user->setPassword($row['password']);
            $user->setName($row['name']);
            $user->setCreatedAt(new \DateTime($row['created_at']));
            $user->setIsActive($row['is_active']);
            $user->setIsVerified($row['is_verified']);
            if ($row['verification_token'] !== null) {
                $user->setVerificationToken($row['verification_token']);
            }
            if ($row['api_token'] !== null) {
                $user->setApiToken($row['api_token']);
            }

            $manager->persist($user);
            $this->addReference($row['ref'], $user);
        }

        $manager->flush();
    }
}
PHP;

        file_put_contents($dir . '/UserFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeCategoryFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $slug = strtolower(str_replace(' ', '_', (string) $row['name']));
            $items[] = [
                'ref' => 'category_' . $row['id'],
                'ref_slug' => 'category_' . $slug,
                'name' => $row['name'],
                'created_by' => $row['created_by_id'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/CategoryFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeProductFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'ref' => 'product_' . $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'price' => (float) $row['price'],
                'image' => $row['image'],
                'stock' => (int) $row['stock'],
                'category_id' => $row['category_id'],
                'created_by' => $row['created_by_id'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/ProductFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeCustomerFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'ref' => 'customer_' . $row['id'],
                'ref_username' => 'customer_' . $row['username'],
                'name' => $row['name'],
                'email' => $row['email'],
                'customer_name' => $row['customer_name'],
                'phone' => $row['phone'],
                'username' => $row['username'],
                'created_by' => $row['created_by_id'],
            ];
        }

        $content = <<<'PHP'
<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = %DATA%;

        foreach ($rows as $row) {
            $customer = new Customer();
            $customer->setName($row['name']);
            $customer->setEmail($row['email']);
            $customer->setCustomerName($row['customer_name']);
            $customer->setPhone($row['phone']);
            $customer->setUsername($row['username']);

            if ($row['created_by'] !== null && $this->hasReference('user_' . $row['created_by'], User::class)) {
                $customer->setCreatedBy($this->getReference('user_' . $row['created_by'], User::class));
            }

            $manager->persist($customer);
            $this->addReference($row['ref'], $customer);
            $this->addReference($row['ref_username'], $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
PHP;

        file_put_contents($dir . '/CustomerFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeOrderFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'ref' => 'order_' . $row['id'],
                'product_name' => $row['product_name'],
                'quantity' => (float) $row['quantity'],
                'price' => (float) $row['price'],
                'status' => $row['status'],
                'order_date' => $row['order_date'],
                'customer_id' => $row['customer_id'],
                'created_by' => $row['created_by_id'],
                'order_number' => $row['order_number'],
                'payment_method' => $row['payment_method'],
                'payment_status' => $row['payment_status'],
                'order_source' => $row['order_source'],
                'shipping_full_name' => $row['shipping_full_name'],
                'shipping_phone' => $row['shipping_phone'],
                'shipping_address' => $row['shipping_address'],
                'shipping_city' => $row['shipping_city'],
                'shipping_postal_code' => $row['shipping_postal_code'],
                'order_notes' => $row['order_notes'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/OrderFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeCartFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'ref' => 'cart_' . $row['id'],
                'user_id' => $row['user_id'],
                'total' => (float) $row['total'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/CartFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeCartItemFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'ref' => 'cart_item_' . $row['id'],
                'cart_id' => $row['cart_id'],
                'product_id' => $row['product_id'],
                'quantity' => (int) $row['quantity'],
                'price' => (float) $row['price'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/CartItemFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeActivityLogFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'user_id' => $row['user_id'],
                'action' => $row['action'],
                'entity_type' => $row['entity_type'],
                'entity_id' => $row['entity_id'],
                'affected_data' => $row['affected_data'],
                'description' => $row['description'],
                'timestamp' => $row['timestamp'],
                'ip_address' => $row['ip_address'],
            ];
        }

        $content = <<<'PHP'
<?php

namespace App\DataFixtures;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ActivityLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = %DATA%;

        foreach ($rows as $row) {
            $log = new ActivityLog();
            $log->setAction($row['action']);
            $log->setEntityType($row['entity_type']);
            $log->setEntityId($row['entity_id'] !== null ? (int) $row['entity_id'] : null);
            $log->setAffectedData($row['affected_data']);
            $log->setDescription($row['description']);
            $log->setTimestamp(new \DateTime($row['timestamp']));
            $log->setIpAddress($row['ip_address']);

            if ($row['user_id'] !== null && $this->hasReference('user_' . $row['user_id'], User::class)) {
                $log->setUser($this->getReference('user_' . $row['user_id'], User::class));
            }

            $manager->persist($log);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
PHP;

        file_put_contents($dir . '/ActivityLogFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function writeNotificationFixtures(string $dir, array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'user_id' => $row['user_id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'order_id' => $row['order_id'],
                'order_number' => $row['order_number'],
                'is_read' => (bool) $row['is_read'],
                'created_at' => $row['created_at'],
            ];
        }

        $content = <<<'PHP'
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
        $rows = %DATA%;

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
PHP;

        file_put_contents($dir . '/NotificationFixtures.php', str_replace('%DATA%', $this->exportArray($items), $content));
    }

    private function writeAppFixtures(string $dir): void
    {
        $content = <<<'PHP'
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
PHP;

        file_put_contents($dir . '/AppFixtures.php', $content);
    }

    /**
     * @param array<mixed> $data
     */
    private function exportArray(array $data): string
    {
        $json = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return 'json_decode(' . var_export($json, true) . ', true, 512, JSON_THROW_ON_ERROR)';
    }
}
