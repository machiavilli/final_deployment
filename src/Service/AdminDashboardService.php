<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Builds live dashboard metrics and chart series from the database.
 */
final class AdminDashboardService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $now = new \DateTimeImmutable('today');
        $last30Start = $now->modify('-29 days');
        $prev30Start = $now->modify('-59 days');
        $prev30End = $now->modify('-30 days');

        $totalProducts = $this->safeCount(Product::class);
        $totalOrders = $this->safeCount(Order::class);
        $totalCustomers = $this->countCustomers();
        $totalRevenue = $this->sumOrderRevenue();

        return [
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'total_revenue' => $totalRevenue,
            'products_change_pct' => null,
            'orders_change_pct' => $this->percentChange(
                $this->countOrdersSince($last30Start),
                $this->countOrdersBetween($prev30Start, $prev30End),
            ),
            'customers_change_pct' => $this->percentChange(
                $this->countUsersRegisteredSince($last30Start),
                $this->countUsersRegisteredBetween($prev30Start, $prev30End),
            ),
            'revenue_change_pct' => $this->percentChange(
                $this->sumOrderRevenueSince($last30Start),
                $this->sumOrderRevenueBetween($prev30Start, $prev30End),
            ),
            'sales_chart' => $this->buildDailySalesChart($now->modify('-6 days'), $now),
            'category_chart' => $this->buildCategoryChart(),
            'revenue_chart' => $this->buildMonthlyRevenueChart($now),
            'recent_activities' => $this->activityLogRepository->findRecent(8),
        ];
    }

    /**
     * JSON payload for admin dashboard live polling (orders, revenue, charts).
     *
     * @return array<string, mixed>
     */
    public function buildSyncPayload(): array
    {
        $data = $this->getDashboardData();

        return [
            'version' => $this->computeSyncVersion($data),
            'total_products' => $data['total_products'],
            'total_orders' => $data['total_orders'],
            'total_customers' => $data['total_customers'],
            'total_revenue' => round($data['total_revenue'], 2),
            'orders_change_pct' => $data['orders_change_pct'],
            'customers_change_pct' => $data['customers_change_pct'],
            'revenue_change_pct' => $data['revenue_change_pct'],
            'sales_chart' => $data['sales_chart'],
            'category_chart' => $data['category_chart'],
            'revenue_chart' => $data['revenue_chart'],
            'recent_activities' => $this->serializeActivities($data['recent_activities']),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function computeSyncVersion(array $data): string
    {
        $latestActivityId = 0;
        foreach ($data['recent_activities'] as $activity) {
            if ($activity instanceof \App\Entity\ActivityLog && $activity->getId()) {
                $latestActivityId = max($latestActivityId, (int) $activity->getId());
            }
        }

        return sprintf(
            'o%d-c%d-r%.2f-a%d',
            (int) $data['total_orders'],
            (int) $data['total_customers'],
            (float) $data['total_revenue'],
            $latestActivityId,
        );
    }

    /**
     * @param iterable<mixed> $activities
     *
     * @return list<array<string, string>>
     */
    private function serializeActivities(iterable $activities): array
    {
        $rows = [];
        foreach ($activities as $activity) {
            if (!$activity instanceof \App\Entity\ActivityLog) {
                continue;
            }
            $action = (string) $activity->getAction();
            $rows[] = [
                'username' => $activity->getUser()?->getUsername() ?? 'System',
                'action' => $action,
                'description' => (string) ($activity->getDescription()
                    ?: $action . ' ' . $activity->getEntityType()),
                'timestamp' => $activity->getTimestamp()?->format('Y-m-d H:i:s') ?? '',
                'icon' => match ($action) {
                    'CREATE' => 'plus',
                    'UPDATE' => 'edit',
                    'DELETE' => 'trash',
                    'LOGIN' => 'sign-in-alt',
                    'LOGOUT' => 'sign-out-alt',
                    default => 'bell',
                },
            ];
        }

        return $rows;
    }

    private function safeCount(string $entityClass): int
    {
        try {
            return (int) $this->em->getRepository($entityClass)->count([]);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countCustomers(): int
    {
        $customerCount = $this->safeCount(Customer::class);
        if ($customerCount > 0) {
            return $customerCount;
        }

        try {
            return $this->userRepository->countByRole('ROLE_USER');
        } catch (\Throwable) {
            return $this->safeCount(User::class);
        }
    }

    private function sumOrderRevenue(): float
    {
        try {
            $result = $this->orderRepository->createQueryBuilder('o')
                ->select('COALESCE(SUM(o.price * o.quantity), 0)')
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function sumOrderRevenueSince(\DateTimeImmutable $since): float
    {
        return $this->sumOrderRevenueBetween($since, new \DateTimeImmutable('tomorrow'));
    }

    private function sumOrderRevenueBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
    {
        try {
            $result = $this->orderRepository->createQueryBuilder('o')
                ->select('COALESCE(SUM(o.price * o.quantity), 0)')
                ->andWhere('o.orderDate >= :start')
                ->andWhere('o.orderDate < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function countOrdersSince(\DateTimeImmutable $since): int
    {
        return $this->countOrdersBetween($since, new \DateTimeImmutable('tomorrow'));
    }

    private function countOrdersBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        try {
            return (int) $this->orderRepository->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->andWhere('o.orderDate >= :start')
                ->andWhere('o.orderDate < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countUsersRegisteredSince(\DateTimeImmutable $since): int
    {
        return $this->countUsersRegisteredBetween($since, new \DateTimeImmutable('tomorrow'));
    }

    private function countUsersRegisteredBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        try {
            return (int) $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->andWhere('u.createdAt >= :start')
                ->andWhere('u.createdAt < :end')
                ->andWhere('u.roles LIKE :role')
                ->andWhere('u.roles NOT LIKE :admin')
                ->andWhere('u.roles NOT LIKE :staff')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->setParameter('role', '%ROLE_USER%')
                ->setParameter('admin', '%ROLE_ADMIN%')
                ->setParameter('staff', '%ROLE_STAFF%')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function buildDailySalesChart(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $labels = [];
        $values = [];
        $buckets = [];

        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('D');
            $buckets[$key] = 0.0;
        }

        try {
            $orders = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.orderDate >= :start')
                ->andWhere('o.orderDate < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end->modify('+1 day'))
                ->getQuery()
                ->getResult();

            foreach ($orders as $order) {
                if (!$order instanceof Order) {
                    continue;
                }
                $key = $order->getOrderDate()?->format('Y-m-d');
                if ($key !== null && isset($buckets[$key])) {
                    $buckets[$key] += (float) $order->getPrice() * (float) $order->getQuantity();
                }
            }
        } catch (\Throwable) {
            // keep zeros
        }

        foreach (array_keys($buckets) as $key) {
            $values[] = round($buckets[$key], 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildCategoryChart(): array
    {
        try {
            $rows = $this->productRepository->createQueryBuilder('p')
                ->select('c.name AS categoryName', 'COUNT(p.id) AS productCount')
                ->leftJoin('p.category', 'c')
                ->groupBy('c.name')
                ->orderBy('productCount', 'DESC')
                ->getQuery()
                ->getArrayResult();

            $grouped = [];
            foreach ($rows as $row) {
                $name = $row['categoryName'] ?? null;
                $label = ($name === null || $name === '') ? 'Uncategorized' : (string) $name;
                $grouped[$label] = ($grouped[$label] ?? 0) + (int) ($row['productCount'] ?? 0);
            }

            if ($grouped !== []) {
                return [
                    'labels' => array_keys($grouped),
                    'values' => array_values($grouped),
                ];
            }
        } catch (\Throwable) {
            // fall through
        }

        return ['labels' => ['No products yet'], 'values' => [0]];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function buildMonthlyRevenueChart(\DateTimeImmutable $today): array
    {
        $labels = [];
        $values = [];
        $buckets = [];

        for ($i = 5; $i >= 0; --$i) {
            $month = $today->modify("first day of -{$i} months");
            $key = $month->format('Y-m');
            $labels[] = $month->format('M');
            $buckets[$key] = 0.0;
        }

        $rangeStart = $today->modify('first day of -5 months')->setTime(0, 0, 0);

        try {
            $orders = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.orderDate >= :start')
                ->setParameter('start', $rangeStart)
                ->getQuery()
                ->getResult();

            foreach ($orders as $order) {
                if (!$order instanceof Order) {
                    continue;
                }
                $key = $order->getOrderDate()?->format('Y-m');
                if ($key !== null && isset($buckets[$key])) {
                    $buckets[$key] += (float) $order->getPrice() * (float) $order->getQuantity();
                }
            }
        } catch (\Throwable) {
            // keep zeros
        }

        foreach (array_keys($buckets) as $key) {
            $values[] = round($buckets[$key], 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
