<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\RecommendationService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RecommendationServiceTest extends TestCase
{
    private function createProduct(int $id, string $name, string $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setCategory($category);

        // Forcer l'ID via la réflexion car pas de base de données
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($product, $id);

        return $product;
    }

    public function testGetRecommendationsReturnsSameCategoryProducts(): void
    {
        $product = $this->createProduct(1, 'Masque de vampire', 'masques');

        $recommended1 = $this->createProduct(2, 'Masque de zombie', 'masques');
        $recommended2 = $this->createProduct(3, 'Masque de sorcière', 'masques');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects($this->once())
            ->method('findByCategory')
            ->with('masques', 1)
            ->willReturn([$recommended1, $recommended2]);

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(300);
                return $callback($item);
            });

        $service = new RecommendationService($productRepository, $cache);
        $result = $service->getRecommendations($product);

        $this->assertCount(2, $result);
        $this->assertSame($recommended1, $result[0]);
        $this->assertSame($recommended2, $result[1]);
    }

    public function testGetRecommendationsReturnsEmptyArrayWhenNoProducts(): void
    {
        $product = $this->createProduct(5, 'Potion magique', 'potions');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects($this->once())
            ->method('findByCategory')
            ->with('potions', 5)
            ->willReturn([]);

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())->method('expiresAfter')->with(300);
                return $callback($item);
            });

        $service = new RecommendationService($productRepository, $cache);
        $result = $service->getRecommendations($product);

        $this->assertCount(0, $result);
        $this->assertIsArray($result);
    }

    public function testGetRecommendationsUsesCacheKey(): void
    {
        $product = $this->createProduct(10, 'Cape de vampire', 'costumes');

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('findByCategory')->willReturn([]);

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->stringContains('recommendations_'),
                $this->isCallable()
            )
            ->willReturn([]);

        $service = new RecommendationService($productRepository, $cache);
        $service->getRecommendations($product);
    }
}