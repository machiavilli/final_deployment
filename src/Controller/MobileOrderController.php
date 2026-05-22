<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mobile/orders')]
class MobileOrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_mobile_orders_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['product_name']) || !isset($data['quantity']) || !isset($data['price'])) {
                $response = ApiResponse::error('Missing required fields: product_name, quantity, price');
                return $this->json($response->toArray(), 400);
            }

            $order = new Order();
            $order->setProductName($data['product_name']);
            $order->setQuantity($data['quantity']);
            $order->setPrice($data['price']);
            $order->setStatus('pending');
            $order->setOrderDate(new \DateTime());

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            $orderData = [
                'id' => $order->getId(),
                'product_name' => $order->getProductName(),
                'quantity' => $order->getQuantity(),
                'price' => $order->getPrice(),
                'status' => $order->getStatus(),
                'order_date' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                'customer' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ]
            ];

            $response = ApiResponse::success('Order created successfully', $orderData);
            return $this->json($response->toArray(), 201);
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to create order', [$e->getMessage()]);
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('', name: 'api_mobile_orders_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $email = $user->getEmail();
            $orders = $email
                ? $this->orderRepository->createQueryBuilder('o')
                    ->leftJoin('o.customer', 'c')
                    ->addSelect('c')
                    ->where('c.email = :email')
                    ->setParameter('email', $email)
                    ->orderBy('o.orderDate', 'DESC')
                    ->addOrderBy('o.id', 'DESC')
                    ->getQuery()
                    ->getResult()
                : [];
            
            $orderData = [];
            foreach ($orders as $order) {
                $orderData[] = $this->serializeOrder($order);
            }

            $response = ApiResponse::success(
                'Orders retrieved successfully',
                $orderData,
                ['total' => count($orderData)]
            );

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve orders');
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('/{id}', name: 'api_mobile_orders_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            $order = $this->orderRepository->find($id);

            $customer = $order?->getCustomer();
            if (
                !$order
                || !$customer
                || $customer->getEmail() !== $user->getEmail()
            ) {
                $response = ApiResponse::error('Order not found');
                return $this->json($response->toArray(), 404);
            }

            $orderData = array_merge($this->serializeOrder($order), [
                'customer' => [
                    'id' => $customer->getId(),
                    'name' => $customer->getName(),
                    'email' => $customer->getEmail(),
                ],
            ]);

            $response = ApiResponse::success('Order retrieved successfully', $orderData);
            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve order');
            return $this->json($response->toArray(), 500);
        }
    }

    private function serializeOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'product_name' => $order->getProductName(),
            'quantity' => $order->getQuantity(),
            'price' => $order->getPrice(),
            'line_total' => $order->getLineTotal(),
            'status' => $order->getStatus(),
            'order_number' => $order->getOrderNumber(),
            'payment_method' => $order->getPaymentMethod(),
            'payment_status' => $order->getPaymentStatus(),
            'order_date' => $order->getOrderDate()?->format('Y-m-d H:i:s'),
            'shipping_city' => $order->getShippingCity(),
        ];
    }
}
