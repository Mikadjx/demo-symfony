<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private RecommendationService $recommendationService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

        $products = $this->productRepository->findPaginated($page, $limit);

        return $this->json(array_map(
            fn($p) => $this->serialize($p),
            $products
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw new NotFoundHttpException("Produit $id introuvable.");
        }

        $recommendations = $this->recommendationService->getRecommendations($product);

        return $this->json([
            'product'         => $this->serialize($product),
            'recommendations' => array_map(fn($p) => $this->serialize($p), $recommendations),
        ]);
    }

    private function serialize($product): array
    {
        return [
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice(),
            'category'    => $product->getCategory(),
            'stock'       => $product->getStock(),
        ];
    }
}