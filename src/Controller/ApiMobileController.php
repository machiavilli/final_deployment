<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiMobileController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('/api/mobile/products/list', name: 'api_mobile_products_alt', methods: ['GET'])]
    public function getProducts(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        $data = array_map(function ($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'description' => $product->getDescription(),
                'image' => $product->getImage(),
                'category' => $product->getCategory() ? $product->getCategory()->getName() : null,
            ];
        }, $products);

        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'count' => count($data)
        ], Response::HTTP_OK);
    }

    #[Route('/api/mobile/categories', name: 'api_mobile_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = $this->categoryRepository->findStorefrontCategories();

        $data = array_map(function ($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'product_count' => $category->getProducts()->count(),
            ];
        }, $categories);

        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'count' => count($data)
        ], Response::HTTP_OK);
    }

    #[Route('/api/mobile/contact', name: 'api_mobile_contact', methods: ['POST'])]
    public function submitContact(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['email']) || !isset($data['message'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Name, email, and message are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Basic validation
        if (empty(trim($data['name'])) || empty(trim($data['email'])) || empty(trim($data['message']))) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'All fields must be filled'
            ], Response::HTTP_BAD_REQUEST);
        }

        // In a real app, send email or save to DB
        // For now, just return success

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Contact form submitted successfully'
        ], Response::HTTP_OK);
    }
}