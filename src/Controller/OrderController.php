<?php

namespace App\Controller;

use App\Entity\OrderProduct;
use App\Service\StripePayment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Order;
use App\Form\OrderType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\City;
use App\Repository\OrderRepository;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Mailer\MailerInterface;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) {}

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        Cart $cart,
    ): Response {
        // Récupérer le panier d'achat depuis la session
        $data = $cart->getCart($session);

        // Création et gestion du formulaire de commande
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form = $form->handleRequest($request);

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            if (!empty($data['total'])) {
                // Calcul du prix total avec les frais de livraison
                $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                $order->setTotalPrice($totalPrice);
                $order->setCreatedAt(new \DateTimeImmutable());
                $order->setIsPaymentCompleted(0);

                // Sauvegarde de la commande en base de données
                $entityManager->persist($order);
                $entityManager->flush();

                // Associer les produits commandés à la commande
                foreach ($data['cart'] as $value) {
                    $orderProduct = new OrderProduct();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($value['product']);
                    $orderProduct->setQte($value['quantity']);

                    $entityManager->persist($orderProduct);
                    $entityManager->flush();
                }

                // Vérifier si le paiement est à la livraison
                if ($order->isPayOneDelivery()) {
                    $session->set('cart', []);

                    // Envoi d'un email de confirmation de commande
                    $html = $this->renderView('mail/orderConfirmMail.html.twig', [
                        'order' => $order,
                    ]);

                    $mail = (new Email())
                        ->from('myShop@gmail.com')
                        ->to($order->getEmail())
                        ->subject('Confirmation de la réception de la commande')
                        ->html($html);

                    $this->mailer->send($mail);
                    return $this->redirectToRoute('order-ok-message');
                }

                // Initialisation du paiement Stripe
                $payment = new StripePayment();
                $shippingCost = $order->getCity()->getShippingCost();
                $payment->startPayment($data, $shippingCost, $order->getId());

                return $this->redirect($payment->getStripeRedirectUrl());
            }
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    // Afficher les commandes selon leur type pour les éditeurs et administrateurs
    #[Route('/editor/order/{type}/', name: 'app_orders_show')]
    public function getAllOrder($type, OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator): Response
    {
        // Filtrer les commandes selon le type passé en paramètre
        if ($type == 'is-completed') {
            $data = $orderRepository->findBy(['isCompleted' => 1], ['id' => 'DESC']);
        } elseif ($type == 'pay-on-stripe-not-delivered') {
            $data = $orderRepository->findBy(['isCompleted' => null, 'payOneDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } elseif ($type == 'pay-on-stripe-is-delivered') {
            $data = $orderRepository->findBy(['isCompleted' => 1, 'payOneDelivery' => 0, 'isPaymentCompleted' => 1], ['id' => 'DESC']);
        } elseif ($type == 'payer-livraison-et-non-livrer') {
            $data = $orderRepository->findBy(['isCompleted' => null, 'payOneDelivery' => 1], ['id' => 'DESC']);
        } elseif ($type == 'payer-livraison-et-deja-livrer') {
            $data = $orderRepository->findBy(['isCompleted' => 1, 'payOneDelivery' => 1], ['id' => 'DESC']);
        }

        // Paginer les résultats
        $order = $paginator->paginate(
            $data,
            $request->query->getInt('page', default: 1),
            limit: 10
        );

        return $this->render('order/order.html.twig', [
            'orders' => $order
        ]);
    }

    // Marquer une commande comme complétée
    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is_completed_update')]
    public function isCompletedUpdate($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash('success', 'Votre modification a été effectuée');

        return $this->redirect($request->headers->get('referer'));
    }

    // Supprimer une commande
    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('danger', 'Votre commande a été supprimée');

        return $this->redirect($request->headers->get('referer'));
    }

    // Afficher un message après une commande réussie
    #[Route('/order-ok-message', name: 'order-ok-message')]
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }

    // Récupérer le coût de livraison d'une ville spécifique
    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();
        return new Response(json_encode(['status' => 200, 'message' => 'ok', 'content' => $cityShippingPrice]));
    }
}
