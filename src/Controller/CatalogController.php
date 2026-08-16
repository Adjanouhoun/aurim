<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/boutique', name: 'app_catalog')]
    public function index(Request $request, ProductRepository $products, EntityManagerInterface $entityManager): Response
    {
        $categorySlug = trim((string) $request->query->get('categorie'));
        $categories = $entityManager->getRepository(Category::class)->findBy(['active' => true], ['position' => 'ASC', 'name' => 'ASC']);

        return $this->render('catalog/index.html.twig', [
            'products' => $products->findActive($categorySlug ?: null),
            'categories' => $categories,
            'selectedCategory' => $categorySlug,
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_product_show')]
    public function show(string $slug, ProductRepository $products): Response
    {
        $product = $products->findOneBy(['slug' => $slug, 'active' => true]);

        if (null === $product) {
            throw $this->createNotFoundException('Ce produit AURIM est introuvable.');
        }

        return $this->render('catalog/show.html.twig', ['product' => $product]);
    }
}
