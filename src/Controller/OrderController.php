<?php

namespace App\Controller;

use App\Entity\OrderProduct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\City;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, SessionInterface $session, ProductRepository $productRepository, EntityManagerInterface $entityManager, Cart $cart): Response
    {
        // Récupérer le panier d'achat depuis la session
        $data = $cart->getCart($session);



        // $cart = $session->get('cart', []);
        // $cartWhitData = [];
        // foreach ($cart as $id => $quantity) {
        //     $cartWhitData[] = [
        //         'product' => $productRepository->find($id),
        //         'quantity' => $quantity
        //     ];
        // }
        // // Calculer le prix total
        // $total = array_sum(array_map(function ($item) {
        //     return $item['product']->getPrice() * $item['quantity'];
        // }, $cartWhitData));

        // Création du formulaire de commande
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form = $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->isPayOneDelivery()) {
                if (!empty($data['total'])) {
                    //dd($order);
                    $order->setTotalPrice($data['total']);
                    $order->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($order);
                    $entityManager->flush();

                    foreach ($data['cart'] as $value) {
                        $orderProduct = new OrderProduct();
                        $orderProduct->setOrder($order);
                        $orderProduct->setProduct($value['product']);
                        $orderProduct->setQte($value['quantity']);
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                        //dd($data['cart']);

                    }
                }
                $session->set('cart', []);
                return $this->redirectToRoute('order-ok-message');
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    #[Route('/order-ok-message', name:'order-ok-message')]
    public function orderMessage():Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        //dd($city);
        $cityShippingPrice = $city->getShippingCost();
        return new Response(json_encode(['status' => 200, 'message' => 'ok', 'content' => $cityShippingPrice]));
    }
}
