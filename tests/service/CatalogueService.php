<?php

namespace App\Service;

use App\Entity\Product;

class CatalogueService
{
    /**
     * Retourne uniquement les produits disponibles en stock
     * Utilisé pour afficher le catalogue aux utilisateurs connectés
     *
     * @param Product[] $products
     * @return Product[]
     */
    public function filterAvailableProducts(array $products): array
    {
        return array_values(array_filter(
            $products,
            fn(Product $p) => $p->getStock() > 0
        ));
    }

    /**
     * Retourne les produits d'une catégorie donnée
     * Utilisé pour le filtrage par catégorie dans le catalogue
     *
     * @param Product[] $products
     * @return Product[]
     */
    public function filterByCategory(array $products, string $category): array
    {
        return array_values(array_filter(
            $products,
            fn(Product $p) => $p->getCategory() === $category
        ));
    }

    /**
     * Trie les produits par prix croissant
     *
     * @param Product[] $products
     * @return Product[]
     */
    public function sortByPriceAsc(array $products): array
    {
        $sorted = $products;
        usort($sorted, fn(Product $a, Product $b) => (float) $a->getPrice() <=> (float) $b->getPrice());
        return $sorted;
    }
}