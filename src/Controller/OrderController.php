<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Service\ActivityLogService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_STAFF')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAllForPanel();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityLogService $logService, ProductRepository $productRepository): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productName = (string) $order->getProductName();
            $quantity = (int) $order->getQuantity();

            if ($productName === '') {
                $this->addFlash('error', 'Product name is required.');
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            if ($quantity <= 0) {
                $this->addFlash('error', 'Quantity must be greater than 0.');
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            // Deduct stock from the matching product by name.
            $product = $productRepository->findOneBy(['name' => $productName]);

            if (!$product) {
                $this->addFlash('error', 'No product found for that product name. Stock could not be deducted.');

                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $availableStock = $product->getStock();
            if ($availableStock < $quantity) {
                $this->addFlash('error', sprintf('Not enough stock. Available: %d, requested: %d.', $availableStock, $quantity));

                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $product->setStock($availableStock - $quantity);
            $entityManager->persist($product);

            // Set createdBy for ownership tracking
            $order->setCreatedBy($this->getUser());
            
            // Set orderDate if not already set
            if (!$order->getOrderDate()) {
                $order->setOrderDate(new \DateTime());
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $logService->logCreate($this->getUser(), 'Order', $order->getId(), [
                'productName' => $order->getProductName(),
                'quantity' => (string) $order->getQuantity()
            ]);

            // Track stock changes for auditing.
            $logService->logUpdate($this->getUser(), 'Product', $product->getId(), [
                'name' => $product->getName(),
                'stock_change' => '-' . (string) $quantity,
                'stock_after' => (string) $product->getStock(),
            ]);

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, ActivityLogService $logService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$order->isWebsiteOrder() && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own records.');
        }

        $previousStatus = $order->getStatus();
        $previousPaymentStatus = $order->getPaymentStatus();

        $form = $this->createForm(OrderType::class, $order, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = $form->get('status')->getData();
            if ($status !== null) {
                $order->setStatus((string) $status);
            }

            $paymentStatus = $form->get('paymentStatus')->getData();
            if ($paymentStatus !== null) {
                $order->setPaymentStatus((string) $paymentStatus);
                if ($paymentStatus === 'paid' && $order->getStatus() === 'pending') {
                    $order->setStatus('processing');
                }
            }

            $entityManager->flush();

            $this->notificationService->notifyOrderChanges(
                $order,
                $previousStatus,
                $previousPaymentStatus
            );

            $logService->logUpdate($this->getUser(), 'Order', $order->getId(), [
                'productName' => $order->getProductName(),
                'status' => $order->getStatus(),
                'paymentStatus' => $order->getPaymentStatus(),
            ]);

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager, ActivityLogService $logService): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$order->isWebsiteOrder() && $order->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own records.');
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $orderData = [
                'productName' => $order->getProductName(),
                'quantity' => (string) $order->getQuantity()
            ];
            $entityManager->remove($order);
            $entityManager->flush();
            $logService->logDelete($this->getUser(), 'Order', $orderId, $orderData);
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
