<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository): Response
    {
        // Récupérer le panier d'achat depuis la session
        $cart = $session->get('cart', []);
        $cartWhitData = [];
        foreach ($cart as $id => $quantity) {
            $cartWhitData[] = [
                'product' => $productRepository->find($id),
                'quantity' => $quantity
            ];
        }
        // Calculer le prix total
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWhitData));

        // Création du formulaire de commande
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form = $form->handleRequest($request);

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $total
        ]);
    }
}
