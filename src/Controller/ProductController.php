<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CatalogueService;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
final class ProductController extends AbstractController
{
    public function __construct(
        private CatalogueService $catalogueService,
        private RecommendationService $recommendationService
    ) {}

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository, Request $request): Response
    {
        $all = $productRepository->findAll();

        // US-01 — filtre stock > 0 via CatalogueService (9 tests unitaires)
        $products = $this->catalogueService->filterAvailableProducts($all);

        // Filtre catégorie optionnel — getString() garantit string, jamais null
        $category = $request->query->getString('category') ?: null;
        if ($category !== null) {
            $products = $this->catalogueService->filterByCategory($products, $category);
        }

        // Tri par prix optionnel
        $sort = $request->query->getString('sort') ?: null;
        if ($sort === 'price_asc') {
            $products = $this->catalogueService->sortByPriceAsc($products);
        }

        // US-02 — recommandations sur le premier produit disponible (cache TTL 300s)
        $recommended = !empty($products)
            ? $this->recommendationService->getRecommendations($products[0])
            : [];

        return $this->render('product/index.html.twig', [
            'products'       => $products,
            'recommended'    => $recommended,
            'activeCategory' => $category,
            'activeSort'     => $sort,
        ]);
    }
}