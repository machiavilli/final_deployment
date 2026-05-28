<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds JSON payloads for the staff orders panel live refresh (polling).
 */
class OrderPanelSyncService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array{version: string, stats: array<string, int>, orders: list<array<string, mixed>>}
     */
    public function buildSyncPayload(): array
    {
        $orders = $this->orderRepository->findAllForPanel();

        return [
            'version' => $this->computeVersion($orders),
            'stats' => $this->computeStats($orders),
            'orders' => array_map(fn (Order $order) => $this->serializeOrderRow($order), $orders),
        ];
    }

    /**
     * @param list<Order> $orders
     */
    private function computeVersion(array $orders): string
    {
        if ($orders === []) {
            return 'empty';
        }

        $maxId = 0;
        $latestTs = 0;
        foreach ($orders as $order) {
            $maxId = max($maxId, (int) $order->getId());
            $date = $order->getOrderDate();
            if ($date) {
                $latestTs = max($latestTs, $date->getTimestamp());
            }
        }

        return sprintf('%d-%d-%d', $maxId, \count($orders), $latestTs);
    }

    /**
     * @param list<Order> $orders
     *
     * @return array{total: int, pending: int, completed: int, cancelled: int, website: int}
     */
    private function computeStats(array $orders): array
    {
        $stats = [
            'total' => \count($orders),
            'pending' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'website' => 0,
        ];

        foreach ($orders as $order) {
            $status = strtolower((string) $order->getStatus());
            if ($status === 'pending') {
                ++$stats['pending'];
            } elseif ($status === 'completed' || $status === 'delivered') {
                ++$stats['completed'];
            } elseif ($status === 'cancelled') {
                ++$stats['cancelled'];
            }
            if ($order->isWebsiteOrder()) {
                ++$stats['website'];
            }
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrderRow(Order $order): array
    {
        $id = (int) $order->getId();
        $orderDate = $order->getOrderDate();

        return [
            'id' => $id,
            'orderNumber' => $order->getOrderNumber() ?? ('#' . $id),
            'customerName' => $order->getCustomer()?->getName() ?? 'N/A',
            'productName' => $order->getProductName(),
            'quantity' => (int) round((float) $order->getQuantity()),
            'lineTotal' => number_format($order->getLineTotal(), 2, '.', ''),
            'lineTotalFormatted' => '₱' . number_format($order->getLineTotal(), 2),
            'paymentStatus' => strtolower((string) ($order->getPaymentStatus() ?? 'pending')),
            'paymentStatusLabel' => $order->getPaymentStatusLabel(),
            'paymentMethodLabel' => $order->getPaymentMethodLabel(),
            'status' => strtolower((string) $order->getStatus()),
            'statusLabel' => $order->getStatus(),
            'orderSource' => $order->isWebsiteOrder() ? 'Website' : 'Manual',
            'orderDate' => $orderDate?->format('Y-m-d') ?? '',
            'orderDateLabel' => $orderDate?->format('M d, Y') ?? '',
            'showUrl' => $this->urlGenerator->generate('app_order_show', ['id' => $id]),
            'editUrl' => $this->urlGenerator->generate('app_order_edit', ['id' => $id]),
        ];
    }
}
