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

    /// 1- Cette route permet de récupérer les produits du panier
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {
        // Récupérer les données du panier à partir de la session
        $data = $cart->getCart($session);

        // Retourner la vue avec les produits du panier et le total
        return $this->render('cart/index.html.twig', [
            'items' => $data['cart'],
            'total' => $data['total']
        ]);
    }


    /// 2- Cette fonction permet d'ajouter un produit au panier
    #[Route('/cart/add/{id}', name: 'app_cart_new', methods: ['GET'])]
    public function addToCart(int $id, SessionInterface $session): Response
    {
        // Récupérer le panier depuis la session
        $cart = $session->get('cart', []);

        // Vérifier si le produit est déjà dans le panier
        if (!empty($cart[$id])) {
            // Incrémenter la quantité du produit
            $cart[$id]++;
        } else {
            // Ajouter le produit avec une quantité de 1
            $cart[$id] = 1;
        }

        // Mettre à jour la session avec le nouveau panier
        $session->set('cart', $cart);

        // Rediriger vers la page du panier
        return $this->redirectToRoute('app_cart');
    }


    /// 3- Cette fonction permet de supprimer un produit spécifique du panier
    #[Route('/cart/remove/{id}', name: 'app_cart_product_remove', methods: ['GET'])]
    public function removeToCart($id, SessionInterface $session): Response
    {
        // Récupérer le panier depuis la session
        $cart = $session->get('cart', []);

        // Vérifier si le produit existe dans le panier
        if (!empty($cart[$id])) {
            // Supprimer le produit du panier
            unset($cart[$id]);
        }

        // Mettre à jour la session avec le panier modifié
        $session->set('cart', $cart);

        // Rediriger vers la page du panier
        return $this->redirectToRoute('app_cart');
    }


    /// 4- Cette fonction permet de vider complètement le panier
    #[Route('/cart/remove', name: 'app_cart_remove', methods: ['GET'])]
    public function remove(SessionInterface $session): Response
    {
        // Réinitialiser le panier en le vidant
        $session->set('cart', []);

        // Rediriger vers la page du panier
        return $this->redirectToRoute('app_cart');
    }
}
