<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    //Cette route permette de recuperer les produits de la panier
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {
        //Remplire le panier d'achat du produit
        $data = $cart->getCart($session);





        // $cart = $session->get('cart', []);
        // $cartWhitData = [];
        // foreach ($cart as $id => $quantity) {
        //     $cartWhitData[] = [
        //         'product' => $this->productRepository->find($id),
        //         'quantity' => $quantity
        //     ];
        // }
        // //Renvoyer a l'utilsateur le pri total
        // $total = array_sum(array_map(function ($item) {
        //     return $item['product']->getPrice() * $item['quantity'];
        // }, $cartWhitData));
        //dd($total);

        return $this->render('cart/index.html.twig', [
            'items' => $data['cart'],
            'total' => $data['total']
        ]);
    }

    //Cette fonction permette d'ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'app_cart_new', methods: ['GET'])]
    public function addToCart(int $id, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        $session->set('cart', $cart);
        return $this->redirectToRoute('app_cart');
    }

    //Cette fonction permette de supprimer les elements du panier panier
    #[Route('/cart/remove/{id}', name: 'app_cart_product_remove', methods: ['GET'])]
    public function removeToCart($id, SessionInterface $session): Response
    {

        $cart = $session->get('cart', []);
        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    //Cette fonction permette de supprimer le panier
    #[Route('/cart/remove', name: 'app_cart_remove', methods: ['GET'])]
    public function remove(SessionInterface $session): Response
    {
        $session->set('cart', []);
        return $this->redirectToRoute('app_cart');
    }
}
