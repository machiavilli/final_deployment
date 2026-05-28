<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {}

    public function notifyOrderPlaced(User $user, string $orderNumber, float $total): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType(Notification::TYPE_ORDER_PLACED);
        $notification->setTitle('Order placed');
        $notification->setMessage(sprintf(
            'Your order %s has been placed successfully. Total: ₱%s',
            $orderNumber,
            number_format($total, 2)
        ));
        $notification->setOrderNumber($orderNumber);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function notifyOrderChanges(
        Order $order,
        ?string $previousStatus,
        ?string $previousPaymentStatus,
    ): void {
        $user = $this->resolveUserForOrder($order);
        if (!$user) {
            return;
        }

        $orderNumber = $order->getOrderNumber() ?? ('#' . $order->getId());
        $newStatus = $order->getStatus();
        $newPayment = $order->getPaymentStatus();
        $persisted = false;

        if ($previousStatus !== null && $newStatus !== null && $previousStatus !== $newStatus) {
            $this->persistNotification(
                $user,
                Notification::TYPE_ORDER_STATUS,
                'Order status updated',
                sprintf(
                    'Order %s status changed from %s to %s.',
                    $orderNumber,
                    $this->labelStatus($previousStatus),
                    $this->labelStatus($newStatus)
                ),
                $order->getId(),
                $orderNumber
            );
            $persisted = true;
        }

        if (
            $previousPaymentStatus !== null
            && $newPayment !== null
            && $previousPaymentStatus !== $newPayment
        ) {
            $this->persistNotification(
                $user,
                Notification::TYPE_PAYMENT_STATUS,
                'Payment status updated',
                sprintf(
                    'Order %s payment changed from %s to %s.',
                    $orderNumber,
                    $this->labelPayment($previousPaymentStatus),
                    $this->labelPayment($newPayment)
                ),
                $order->getId(),
                $orderNumber
            );
            $persisted = true;
        }

        if ($persisted) {
            $this->entityManager->flush();
        }
    }

    public function notifyProductCreated(Product $product): void
    {
        $name = $product->getName() ?? 'Product';
        $this->broadcastToAppCustomers(
            Notification::TYPE_PRODUCT_NEW,
            'New product available',
            sprintf('"%s" was just added to the store. Tap to browse.', $name),
        );
    }

    public function notifyProductUpdated(Product $product): void
    {
        $name = $product->getName() ?? 'Product';
        $price = $product->getPrice() !== null
            ? '₱' . number_format($product->getPrice(), 2)
            : '';

        $this->broadcastToAppCustomers(
            Notification::TYPE_PRODUCT_UPDATED,
            'Product updated',
            sprintf(
                '"%s" was updated by the store.%s',
                $name,
                $price !== '' ? ' New price: ' . $price . '.' : ''
            ),
        );
    }

    public function notifyProductRestocked(Product $product, int $amountAdded): void
    {
        $name = $product->getName() ?? 'Product';
        $this->broadcastToAppCustomers(
            Notification::TYPE_PRODUCT_RESTOCK,
            'Back in stock',
            sprintf(
                '"%s" was restocked (+%d). Stock now: %d.',
                $name,
                $amountAdded,
                $product->getStock()
            ),
        );
    }

    private function broadcastToAppCustomers(string $type, string $title, string $message): void
    {
        $users = $this->userRepository->findAppCustomers();
        if ($users === []) {
            return;
        }

        foreach ($users as $user) {
            $this->persistNotification($user, $type, $title, $message, null, null);
        }

        $this->entityManager->flush();
    }

    private function resolveUserForOrder(Order $order): ?User
    {
        $createdBy = $order->getCreatedBy();
        if ($createdBy instanceof User && $this->isAppCustomer($createdBy)) {
            return $createdBy;
        }

        $email = $order->getCustomer()?->getEmail();
        if ($email !== null && $email !== '') {
            $user = $this->userRepository->findOneByCustomerEmail($email);
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    private function isAppCustomer(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array('ROLE_USER', $roles, true)
            && !in_array('ROLE_ADMIN', $roles, true)
            && !in_array('ROLE_STAFF', $roles, true);
    }

    private function persistNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?int $orderId,
        ?string $orderNumber,
    ): void {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setOrderId($orderId);
        $notification->setOrderNumber($orderNumber);

        $this->entityManager->persist($notification);
    }

    private function labelStatus(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    private function labelPayment(string $status): string
    {
        return match ($status) {
            'cod' => 'Cash on Delivery',
            'gcash' => 'GCash',
            'bank' => 'Bank Transfer',
            'awaiting_payment' => 'Awaiting payment',
            'paid' => 'Paid',
            'pending' => 'Pending',
            'failed' => 'Failed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
