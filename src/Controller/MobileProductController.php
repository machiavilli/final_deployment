<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductImageResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mobile/products')]
class MobileProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductImageResolver $imageResolver,
    ) {}

    private function serializeProduct(Product $product, Request $request): array
    {
        $image = $product->getImage();
        $publicPath = $this->imageResolver->resolvePublicPath($image);
        $base = $request->getSchemeAndHttpHost();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stock' => $product->getStock(),
            'category' => $product->getCategory()?->getName(),
            'image' => $image,
            'image_url' => $publicPath
                ? (str_starts_with($publicPath, 'http') ? $publicPath : $base . $publicPath)
                : null,
        ];
    }

    #[Route('', name: 'api_mobile_products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $products = $this->productRepository->findAll();
            
            $productData = [];
            foreach ($products as $product) {
                $productData[] = $this->serializeProduct($product, $request);
            }

            $response = ApiResponse::success(
                'Products retrieved successfully',
                $productData,
                ['total' => count($productData)]
            );

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve products');
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('/search', name: 'api_mobile_products_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $_GET['q'] ?? '';
            $category = $_GET['category'] ?? null;
            $minPrice = $_GET['min_price'] ?? null;
            $maxPrice = $_GET['max_price'] ?? null;

            $products = $this->productRepository->createQueryBuilder('p');

            if (!empty($query)) {
                $products->andWhere('p.name LIKE :query OR p.description LIKE :query')
                    ->setParameter('query', '%' . $query . '%');
            }

            if ($category) {
                $products->join('p.category', 'c')
                    ->andWhere('c.name = :category')
                    ->setParameter('category', $category);
            }

            if ($minPrice !== null) {
                $products->andWhere('p.price >= :minPrice')
                    ->setParameter('minPrice', $minPrice);
            }

            if ($maxPrice !== null) {
                $products->andWhere('p.price <= :maxPrice')
                    ->setParameter('maxPrice', $maxPrice);
            }

            $products = $products->getQuery()->getResult();

            $productData = [];
            foreach ($products as $product) {
                $productData[] = $this->serializeProduct($product, $request);
            }

            $response = ApiResponse::success(
                'Products retrieved successfully',
                $productData,
                [
                    'total' => count($productData),
                    'search_query' => $query,
                    'filters' => [
                        'category' => $category,
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice
                    ]
                ]
            );

            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to search products');
            return $this->json($response->toArray(), 500);
        }
    }

    #[Route('/{id}', name: 'api_mobile_products_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $product = $this->productRepository->find($id);

            if (!$product) {
                $response = ApiResponse::error('Product not found');
                return $this->json($response->toArray(), 404);
            }

            $productData = $this->serializeProduct($product, $request);

            $response = ApiResponse::success('Product retrieved successfully', $productData);
            return $this->json($response->toArray());
        } catch (\Exception $e) {
            $response = ApiResponse::error('Failed to retrieve product');
            return $this->json($response->toArray(), 500);
        }
    }

}
