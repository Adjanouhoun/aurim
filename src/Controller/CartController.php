<?php

namespace App\Controller;

use App\Entity\Product;
use App\Store\Cart;
use App\Store\ProductOffer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class CartController extends AbstractController
{
    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(Cart $cart): Response
    {
        return $this->render('cart/index.html.twig', [
            'lines' => $cart->getLines(),
            'total' => $cart->getTotal(),
            'hasSufficientStock' => $cart->hasSufficientStock(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, Cart $cart, ProductOffer $offer): Response
    {
        $this->assertToken($request, 'cart-add-'.$product->getId());
        $quantity = max(1, $request->request->getInt('quantity', 1));
        if (!$offer->isPurchasable($product, $quantity)) {
            $this->addFlash('cart_error', 'Ce produit n’est pas encore disponible dans le pays sélectionné.');
            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }
        $cart->add($product, $quantity);
        $this->addFlash('success', sprintf('%s a été ajouté au panier.', $product->getName()));

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Product $product, Request $request, Cart $cart): Response
    {
        $this->assertToken($request, 'cart-update-'.$product->getId());
        $cart->update($product, $request->request->getInt('quantity'));

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Product $product, Request $request, Cart $cart): Response
    {
        $this->assertToken($request, 'cart-remove-'.$product->getId());
        $cart->remove($product);

        return $this->redirectToRoute('app_cart');
    }

    private function assertToken(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
    }
}
