<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mobile/categories')]
class MobileCategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    #[Route('', name: 'api_mobile_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $categories = $this->categoryRepository->findStorefrontCategories();
            
            $categoryData = [];
            foreach ($categories as $category) {
                $categoryData[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'updatedAt' => $category->getUpdatedAt()?->format('Y-m-d H:i:s'),
                    'productCount' => count($category->getProducts())
                ];
            }

            $response = ApiResponse::success(
                'Categories retrieved successfully',
                $categoryData,
                ['total' => count($categoryData)]
            );

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve categories');
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('/{id}', name: 'api_mobile_categories_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryRepository->find($id);

            if (!$category) {
                $response = ApiResponse::error('Category not found');
                return $this->json($response->toArray(), 404);
            }

            $products = [];
            foreach ($category->getProducts() as $product) {
                if ($product->isActive()) {
                    $products[] = [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                        'price' => $product->getPrice(),
                        'stock' => $product->getStock(),
                        'image' => $product->getImage(),
                        'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }

            $categoryData = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $category->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'products' => $products,
                'productCount' => count($products)
            ];

            $response = ApiResponse::success('Category retrieved successfully', $categoryData);
            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve category');
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('/{id}/products', name: 'api_mobile_categories_products', methods: ['GET'])]
    public function getProductsByCategory(int $id): JsonResponse
    {
        try {
            $category = $this->categoryRepository->find($id);

            if (!$category) {
                $response = ApiResponse::error('Category not found');
                return $this->json($response->toArray(), 404);
            }

            $products = [];
            foreach ($category->getProducts() as $product) {
                if ($product->isActive()) {
                    $products[] = [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                        'price' => $product->getPrice(),
                        'stock' => $product->getStock(),
                        'image' => $product->getImage(),
                        'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
                        'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s')
                    ];
                }
            }

            $response = ApiResponse::success(
                'Category products retrieved successfully',
                $products,
                [
                    'category_id' => $id,
                    'category_name' => $category->getName(),
                    'total_products' => count($products)
                ]
            );

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve category products');
            return $this->json($response->toArray(), 500);
        }
    }
}
