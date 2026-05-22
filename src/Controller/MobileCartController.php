<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\CheckoutException;
use App\Repository\ProductRepository;
use App\Service\CheckoutService;
use App\Service\ProductImageResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mobile/cart')]
#[IsGranted('ROLE_USER')]
class MobileCartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private CheckoutService $checkoutService,
        private ProductImageResolver $imageResolver,
    ) {}

    #[Route('', name: 'api_mobile_cart_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $cart = $this->getOrCreateCart($user, false);

        $response = ApiResponse::success(
            'Cart retrieved successfully',
            $this->serializeCart($cart, $request),
        );

        return $this->json($response->toArray());
    }

    #[Route('/count', name: 'api_mobile_cart_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        $user = $this->requireUser();
        $cart = $user->getCart();
        $quantity = $cart ? $cart->getTotalQuantity() : 0;

        $response = ApiResponse::success('Cart count retrieved', [
            'count' => $quantity,
            'item_count' => $cart ? $cart->getItemCount() : 0,
        ]);

        return $this->json($response->toArray());
    }

    #[Route('/items', name: 'api_mobile_cart_add', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (!isset($data['product_id'])) {
            $response = ApiResponse::error('Missing required field: product_id');
            return $this->json($response->toArray(), 400);
        }

        $product = $this->productRepository->find((int) $data['product_id']);
        if (!$product) {
            $response = ApiResponse::error('Product not found');
            return $this->json($response->toArray(), 404);
        }

        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        if ($product->getStock() < $quantity) {
            $response = ApiResponse::error(sprintf(
                'Not enough stock. Available: %d',
                $product->getStock()
            ));
            return $this->json($response->toArray(), 400);
        }

        $cart = $this->getOrCreateCart($user, true);
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()?->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->incrementQuantity($quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice((float) $product->getPrice());
            $cart->addCartItem($cartItem);
            $this->entityManager->persist($cartItem);
        }

        $cart->updateTotal();
        $this->entityManager->flush();

        $response = ApiResponse::success(
            'Product added to cart',
            $this->serializeCart($cart, $request),
        );

        return $this->json($response->toArray(), 201);
    }

    #[Route('/items/{id}', name: 'api_mobile_cart_update', methods: ['PATCH', 'PUT'])]
    public function updateItem(int $id, Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $cartItem = $this->findOwnedCartItem($user, $id);
        if (!$cartItem) {
            $response = ApiResponse::error('Cart item not found');
            return $this->json($response->toArray(), 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $quantity = (int) ($data['quantity'] ?? $cartItem->getQuantity());
        $cart = $cartItem->getCart();

        if ($quantity <= 0) {
            $this->entityManager->remove($cartItem);
            $cart?->removeCartItem($cartItem);
        } else {
            $product = $cartItem->getProduct();
            if ($product && $product->getStock() < $quantity) {
                $response = ApiResponse::error(sprintf(
                    'Not enough stock. Available: %d',
                    $product->getStock()
                ));
                return $this->json($response->toArray(), 400);
            }
            $cartItem->setQuantity($quantity);
        }

        $cart?->updateTotal();
        $this->entityManager->flush();

        $response = ApiResponse::success(
            'Cart updated',
            $this->serializeCart($cart ?? $this->getOrCreateCart($user, false), $request),
        );

        return $this->json($response->toArray());
    }

    #[Route('/items/{id}', name: 'api_mobile_cart_remove', methods: ['DELETE'])]
    public function removeItem(int $id, Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $cartItem = $this->findOwnedCartItem($user, $id);
        if (!$cartItem) {
            $response = ApiResponse::error('Cart item not found');
            return $this->json($response->toArray(), 404);
        }

        $cart = $cartItem->getCart();
        $this->entityManager->remove($cartItem);
        $cart?->removeCartItem($cartItem);
        $cart?->updateTotal();
        $this->entityManager->flush();

        $response = ApiResponse::success(
            'Item removed from cart',
            $this->serializeCart($cart ?? $this->getOrCreateCart($user, false), $request),
        );

        return $this->json($response->toArray());
    }

    #[Route('/checkout', name: 'api_mobile_cart_checkout', methods: ['POST'])]
    public function checkout(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $cart = $user->getCart();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $response = ApiResponse::error('Your cart is empty');
            return $this->json($response->toArray(), 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $checkoutData = [
            'fullName' => $data['full_name'] ?? $data['fullName'] ?? $user->getName() ?? '',
            'email' => $data['email'] ?? $user->getEmail() ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'postalCode' => $data['postal_code'] ?? $data['postalCode'] ?? '',
            'paymentMethod' => $data['payment_method'] ?? $data['paymentMethod'] ?? 'cod',
            'notes' => $data['notes'] ?? '',
        ];

        try {
            $result = $this->checkoutService->processCheckout($user, $cart, $checkoutData);
        } catch (CheckoutException $e) {
            $response = ApiResponse::error($e->getMessage());
            return $this->json($response->toArray(), 400);
        }

        $orders = [];
        foreach ($result['orders'] as $order) {
            $orders[] = [
                'id' => $order->getId(),
                'product_name' => $order->getProductName(),
                'quantity' => $order->getQuantity(),
                'price' => $order->getPrice(),
                'status' => $order->getStatus(),
                'order_number' => $order->getOrderNumber(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_status' => $order->getPaymentStatus(),
            ];
        }

        $response = ApiResponse::success('Order placed successfully', [
            'order_number' => $result['orderNumber'],
            'total' => $result['total'],
            'orders' => $orders,
        ]);

        return $this->json($response->toArray(), 201);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function getOrCreateCart(User $user, bool $create): Cart
    {
        $cart = $user->getCart();
        if ($cart) {
            return $cart;
        }

        if (!$create) {
            $cart = new Cart();
            $cart->setUser($user);

            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);
        $user->setCart($cart);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    private function findOwnedCartItem(User $user, int $id): ?CartItem
    {
        $cart = $user->getCart();
        if (!$cart) {
            return null;
        }

        foreach ($cart->getCartItems() as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    private function serializeCart(?Cart $cart, Request $request): array
    {
        if (!$cart || $cart->getId() === null) {
            return [
                'id' => null,
                'total' => 0,
                'item_count' => 0,
                'total_quantity' => 0,
                'items' => [],
            ];
        }

        $base = $request->getSchemeAndHttpHost();
        $items = [];

        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            $image = $product?->getImage();
            $publicPath = $this->imageResolver->resolvePublicPath($image);

            $items[] = [
                'id' => $item->getId(),
                'product_id' => $product?->getId(),
                'product_name' => $product?->getName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'subtotal' => $item->getSubtotal(),
                'stock' => $product?->getStock(),
                'image' => $image,
                'image_url' => $publicPath
                    ? (str_starts_with($publicPath, 'http') ? $publicPath : $base . $publicPath)
                    : null,
            ];
        }

        return [
            'id' => $cart->getId(),
            'total' => $cart->getTotal(),
            'item_count' => $cart->getItemCount(),
            'total_quantity' => $cart->getTotalQuantity(),
            'items' => $items,
        ];
    }
}
