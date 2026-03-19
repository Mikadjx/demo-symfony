<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Service\CatalogueService;
use PHPUnit\Framework\TestCase;

class CatalogueServiceTest extends TestCase
{
    private CatalogueService $service;

    protected function setUp(): void
    {
        $this->service = new CatalogueService();
    }

    // ── Helpers — reproduit les fixtures ProductFixtures ─────────────────────

    private function createProduct(string $name, string $price, string $category, int $stock): Product
    {
        $product = new Product();
        $product->setName($name)
                ->setPrice($price)
                ->setCategory($category)
                ->setStock($stock);
        return $product;
    }

    private function getCatalogueFixtures(): array
    {
        return [
            $this->createProduct('Figurine Dracula',       '34.99', 'figurines', 15),
            $this->createProduct('Figurine Frankenstein',  '29.99', 'figurines', 8),
            $this->createProduct('Blu-ray The Thing',      '24.99', 'blu-ray',   20),
            $this->createProduct('Blu-ray Suspiria',       '22.99', 'blu-ray',   12),
            $this->createProduct('Fanzine Horreur #12',    '8.99',  'fanzine',   50),
            $this->createProduct('Fanzine Heroic Fantasy', '7.99',  'fanzine',   30),
            $this->createProduct('Jeu Horreur Express',    '39.99', 'jeux',      10),
            $this->createProduct('Jeu Cthulhu Wars',       '89.99', 'jeux',      5),
        ];
    }

    // ── filterAvailableProducts ───────────────────────────────────────────────

    public function testAllFixtureProductsAreAvailable(): void
    {
        // Tous les produits fixtures ont un stock > 0
        $products = $this->getCatalogueFixtures();
        $result = $this->service->filterAvailableProducts($products);

        $this->assertCount(8, $result);
    }

    public function testEpuisedProductIsExcludedFromCatalogue(): void
    {
        // Un produit épuisé ne doit pas apparaître dans le catalogue
        $products = $this->getCatalogueFixtures();
        $epuise = $this->createProduct('Figurine Dracula épuisée', '34.99', 'figurines', 0);
        $products[] = $epuise;

        $result = $this->service->filterAvailableProducts($products);

        $this->assertCount(8, $result);
        $this->assertNotContains($epuise, $result);
    }

    public function testReturnsEmptyWhenAllProductsAreOutOfStock(): void
    {
        $products = [
            $this->createProduct('Produit épuisé A', '10.00', 'jeux', 0),
            $this->createProduct('Produit épuisé B', '20.00', 'jeux', 0),
        ];

        $result = $this->service->filterAvailableProducts($products);

        $this->assertEmpty($result);
    }

    // ── filterByCategory ──────────────────────────────────────────────────────

    public function testFilterFigurinesReturnsOnlyFigurines(): void
    {
        // Fixtures : 2 figurines (Dracula + Frankenstein)
        $products = $this->getCatalogueFixtures();
        $result = $this->service->filterByCategory($products, 'figurines');

        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertSame('figurines', $product->getCategory());
        }
    }

    public function testFilterBluRayReturnsOnlyBluRays(): void
    {
        // Fixtures : 2 blu-ray (The Thing + Suspiria)
        $products = $this->getCatalogueFixtures();
        $result = $this->service->filterByCategory($products, 'blu-ray');

        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertSame('blu-ray', $product->getCategory());
        }
    }

    public function testFilterFanzineReturnsOnlyFanzines(): void
    {
        // Fixtures : 2 fanzines (Horreur #12 + Heroic Fantasy)
        $products = $this->getCatalogueFixtures();
        $result = $this->service->filterByCategory($products, 'fanzine');

        $this->assertCount(2, $result);
    }

    public function testFilterUnknownCategoryReturnsEmpty(): void
    {
        $products = $this->getCatalogueFixtures();
        $result = $this->service->filterByCategory($products, 'inexistant');

        $this->assertEmpty($result);
    }

    // ── sortByPriceAsc ────────────────────────────────────────────────────────

    public function testSortByPriceAscReturnsChepeastFirst(): void
    {
        // Fanzine Heroic Fantasy (7.99) doit être en premier
        // Jeu Cthulhu Wars (89.99) doit être en dernier
        $products = $this->getCatalogueFixtures();
        $result = $this->service->sortByPriceAsc($products);

        $this->assertSame('Fanzine Heroic Fantasy', $result[0]->getName());
        $this->assertSame('Jeu Cthulhu Wars', $result[count($result) - 1]->getName());
    }

    public function testSortByPriceAscIsSortedCorrectly(): void
    {
        $products = $this->getCatalogueFixtures();
        $result = $this->service->sortByPriceAsc($products);

        // Vérifier que chaque prix est <= au suivant
        for ($i = 0; $i < count($result) - 1; $i++) {
            $this->assertLessThanOrEqual(
                (float) $result[$i + 1]->getPrice(),
                (float) $result[$i]->getPrice()
            );
        }
    }
}