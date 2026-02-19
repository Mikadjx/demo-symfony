<?php
namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RecommendationService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CacheInterface $cache
    ) {}

    public function getRecommendations(Product $product): array
    {
        $cacheKey = 'recommendations_' . $product->getId();

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($product) {
            $item->expiresAfter(300);
            return $this->productRepository->findByCategory(
                $product->getCategory(),
                $product->getId()
            );
        });
    }
}