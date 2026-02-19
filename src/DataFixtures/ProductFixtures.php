<?php
namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            ['Figurine Dracula', 'Edition limitée résine', 34.99, 'figurines', 15],
            ['Figurine Frankenstein', 'Peinte à la main', 29.99, 'figurines', 8],
            ['Blu-ray The Thing', 'Edition collector 4K', 24.99, 'blu-ray', 20],
            ['Blu-ray Suspiria', 'Restauration 4K Argento', 22.99, 'blu-ray', 12],
            ['Fanzine Horreur #12', 'Numéro spécial slashers', 8.99, 'fanzine', 50],
            ['Fanzine Heroic Fantasy #3', 'Numéro dragons', 7.99, 'fanzine', 30],
            ['Jeu Horreur Express', 'Jeu de plateau 3-6 joueurs', 39.99, 'jeux', 10],
            ['Jeu Cthulhu Wars', 'Edition deluxe', 89.99, 'jeux', 5],
        ];

        foreach ($data as [$name, $desc, $price, $cat, $stock]) {
            $product = new Product();
            $product->setName($name)
                    ->setDescription($desc)
                    ->setPrice($price)
                    ->setCategory($cat)
                    ->setStock($stock);
            $manager->persist($product);
        }

        $manager->flush();
    }
}