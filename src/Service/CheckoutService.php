<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use App\Exception\CheckoutException;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerRepository $customerRepository,
        private readonly ActivityLogService $activityLogService,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * @param array<string, mixed> $checkoutData
     *
     * @return array{orderNumber: string, orders: list<Order>, total: float}
     */
    public function processCheckout(User $user, Cart $cart, array $checkoutData): array
    {
        if ($cart->getCartItems()->isEmpty()) {
            throw new CheckoutException('Your cart is empty.');
        }

        $paymentMethod = (string) ($checkoutData['paymentMethod'] ?? 'cod');
        if (!\in_array($paymentMethod, ['cod', 'gcash', 'bank'], true)) {
            throw new CheckoutException('Invalid payment method selected.');
        }

        $fullName = trim((string) ($checkoutData['fullName'] ?? ''));
        $email = trim((string) ($checkoutData['email'] ?? ''));
        $phone = trim((string) ($checkoutData['phone'] ?? ''));
        $address = trim((string) ($checkoutData['address'] ?? ''));
        $city = trim((string) ($checkoutData['city'] ?? ''));
        $postalCode = trim((string) ($checkoutData['postalCode'] ?? ''));

        if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $city === '' || $postalCode === '') {
            throw new CheckoutException('Please complete all required shipping fields.');
        }

        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            if (!$product) {
                throw new CheckoutException('A product in your cart is no longer available.');
            }
            if ($product->getStock() < $item->getQuantity()) {
                throw new CheckoutException(sprintf(
                    'Not enough stock for "%s". Available: %d, in cart: %d.',
                    $product->getName(),
                    $product->getStock(),
                    $item->getQuantity()
                ));
            }
        }

        $customer = $this->resolveCustomer($user, $fullName, $email, $phone);
        $orderNumber = $this->generateOrderNumber();
        $paymentStatus = $paymentMethod === 'cod' ? 'pending' : 'awaiting_payment';
        $orderNotes = trim((string) ($checkoutData['notes'] ?? ''));
        $orderNotes = $orderNotes !== '' ? $orderNotes : null;

        $createdOrders = [];
        $grandTotal = 0.0;

        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $unitPrice = $item->getPrice();

            $product->setStock($product->getStock() - $quantity);
            $this->entityManager->persist($product);

            $order = new Order();
            $order->setCustomer($customer);
            $order->setProductName($product->getName());
            $order->setQuantity((float) $quantity);
            $order->setPrice($unitPrice);
            $order->setStatus('pending');
            $order->setOrderDate(new \DateTime());
            $order->setCreatedBy($user);
            $order->setOrderNumber($orderNumber);
            $order->setPaymentMethod($paymentMethod);
            $order->setPaymentStatus($paymentStatus);
            $order->setOrderSource('website');
            $order->setShippingFullName($fullName);
            $order->setShippingPhone($phone);
            $order->setShippingAddress($address);
            $order->setShippingCity($city);
            $order->setShippingPostalCode($postalCode);
            $order->setOrderNotes($orderNotes);

            $this->entityManager->persist($order);
            $createdOrders[] = $order;
            $grandTotal += $unitPrice * $quantity;
        }

        foreach ($cart->getCartItems()->toArray() as $item) {
            $this->entityManager->remove($item);
            $cart->removeCartItem($item);
        }
        $cart->updateTotal();

        $this->entityManager->flush();

        foreach ($createdOrders as $order) {
            $this->activityLogService->logCreate($user, 'Order', $order->getId() ?? 0, [
                'orderNumber' => $orderNumber,
                'productName' => $order->getProductName(),
                'quantity' => (string) $order->getQuantity(),
                'source' => 'website',
            ]);
        }

        $this->notificationService->notifyOrderPlaced($user, $orderNumber, $grandTotal);

        return [
            'orderNumber' => $orderNumber,
            'orders' => $createdOrders,
            'total' => $grandTotal,
        ];
    }

    private function resolveCustomer(User $user, string $fullName, string $email, string $phone): Customer
    {
        $customer = $this->customerRepository->findOneBy(['email' => $email]);

        if ($customer instanceof Customer) {
            $customer->setName($fullName);
            $customer->setCustomerName($fullName);
            $customer->setPhone($phone);

            return $customer;
        }

        $username = $user->getUsername() ?? strstr($email, '@', true) ?: 'customer';
        $customer = new Customer();
        $customer->setName($fullName);
        $customer->setEmail($email);
        $customer->setCustomerName($fullName);
        $customer->setUsername($username);
        $customer->setPhone($phone);
        $customer->setCreatedBy(null);

        $this->entityManager->persist($customer);

        return $customer;
    }

    private function generateOrderNumber(): string
    {
        return 'MVLLI-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}
