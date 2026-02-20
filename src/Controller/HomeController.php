<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private RecommendationService $recommendationService
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $products = $this->productRepository->findAll();

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/products/{id}', name: 'app_product')]
    public function product(int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException("Produit $id introuvable.");
        }

        $recommendations = $this->recommendationService->getRecommendations($product);

        return $this->render('home/product.html.twig', [
            'product'         => $product,
            'recommendations' => $recommendations,
        ]);
    }
}