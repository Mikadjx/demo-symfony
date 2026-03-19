<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CatalogueService;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CatalogueService $catalogueService,
        private RecommendationService $recommendationService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $all = $this->productRepository->findAll();

        // Filtre stock > 0 via CatalogueService — cohérent avec ProductController et US-01
        $products = $this->catalogueService->filterAvailableProducts($all);

        // Recommandations basées sur un produit disponible (pas findAll brut)
        $recommended = !empty($products)
            ? $this->recommendationService->getRecommendations($products[0])
            : [];

        return $this->render('home/index.html.twig', [
            'products'    => $products,
            'recommended' => $recommended,
        ]);
    }
}