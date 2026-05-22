<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Order;
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
        }

        $this->entityManager->flush();
    }

    private function resolveUserForOrder(Order $order): ?User
    {
        $email = $order->getCustomer()?->getEmail();
        if (!$email) {
            return null;
        }

        return $this->userRepository->findOneBy(['email' => $email]);
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
