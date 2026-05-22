<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\CheckoutException;
use App\Service\CheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CartController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CheckoutService $checkoutService,
    ) {
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function addToCart(Request $request, Product $product): Response
    {
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user instanceof User) {
            $this->addFlash('error', 'Invalid user type');
            return $this->redirectToRoute('app_home');
        }

        // Get or create cart for user
        $cart = $user->getCart();
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $user->setCart($cart);
            $this->entityManager->persist($cart);
        }

        // Check if product already exists in cart
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        $quantity = (int) $request->request->get('quantity', 1);

        if ($existingItem) {
            $existingItem->incrementQuantity($quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());
            $cart->addCartItem($cartItem);
            $this->entityManager->persist($cartItem);
        }

        $cart->updateTotal();
        $this->entityManager->flush();

        $this->addFlash('success', 'Product added to cart successfully!');

        return $this->redirectToRoute('cart_view');
    }

    #[Route('/cart', name: 'cart_view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function viewCart(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $user->getCart();
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->render('cart/empty.html.twig', [
                'cart' => $cart,
            ]);
        }

        return $this->render('cart/view.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/update/{id}', name: 'cart_update', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateCartItem(Request $request, CartItem $cartItem): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartItem->getCart();
        if ($cart->getUser() !== $user) {
            $this->addFlash('error', 'You can only modify your own cart');
            return $this->redirectToRoute('cart_view');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        if ($quantity <= 0) {
            $this->entityManager->remove($cartItem);
            $cart->removeCartItem($cartItem);
        } else {
            $cartItem->setQuantity($quantity);
        }

        $cart->updateTotal();
        $this->entityManager->flush();

        $this->addFlash('success', 'Cart updated successfully!');
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeCartItem(CartItem $cartItem): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartItem->getCart();
        if ($cart->getUser() !== $user) {
            $this->addFlash('error', 'You can only modify your own cart');
            return $this->redirectToRoute('cart_view');
        }

        $this->entityManager->remove($cartItem);
        $cart->removeCartItem($cartItem);
        $cart->updateTotal();
        $this->entityManager->flush();

        $this->addFlash('success', 'Item removed from cart!');
        return $this->redirectToRoute('cart_view');
    }

    #[Route('/cart/checkout', name: 'cart_checkout', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function checkout(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $user->getCart();
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('cart_view');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid security token. Please try again.');
                return $this->redirectToRoute('cart_checkout');
            }

            try {
                $result = $this->checkoutService->processCheckout($user, $cart, $request->request->all());
            } catch (CheckoutException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('cart_checkout');
            }

            $paymentMethod = (string) $request->request->get('paymentMethod', 'cod');
            $paymentNote = match ($paymentMethod) {
                'gcash', 'bank' => ' We will confirm your payment and update your order status.',
                default => '',
            };

            $this->addFlash('success', sprintf(
                'Order %s placed successfully! Thank you for shopping with MVLLI.%s',
                $result['orderNumber'],
                $paymentNote
            ));

            return $this->redirectToRoute('cart_order_confirmation', [
                'orderNumber' => $result['orderNumber'],
            ]);
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'user' => $user,
        ]);
    }

    #[Route('/cart/order-confirmation/{orderNumber}', name: 'cart_order_confirmation', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function orderConfirmation(string $orderNumber): Response
    {
        return $this->render('cart/confirmation.html.twig', [
            'orderNumber' => $orderNumber,
        ]);
    }

    #[Route('/cart/count', name: 'cart_count', methods: ['GET'])]
    public function getCartCount(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['count' => 0]);
        }

        $cart = $user->getCart();
        $count = $cart ? $cart->getTotalQuantity() : 0;

        return $this->json(['count' => $count]);
    }
}
