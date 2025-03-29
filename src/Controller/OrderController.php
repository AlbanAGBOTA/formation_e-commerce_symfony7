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
use App\Repository\ProductRepository;
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
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        Cart $cart,
        OrderRepository $orderRepository
    ): Response {
        // Récupérer le panier d'achat depuis la session
        $data = $cart->getCart($session);

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

                $html = $this->renderView('mail/orderConfirmMail.html.twig', [
                    'order' => $order,
                ]);

                $mail = (new Email())
                    ->from('myShop@gmail.com')
                    ->to($order->getEmail())
                    ->subject('confirmation de la reception de la commande')
                    ->html($html);

                $this->mailer->send($mail);
                return $this->redirectToRoute('order-ok-message');
            }

            $payment = new StripePayment();

            $shippingCost=$order->getCity()->getShippingCost();

            $payment->startPayment($data, $shippingCost);

            $stripeRedirectUrl = $payment->getStripeRedirectUrl();

            return $this->redirect($stripeRedirectUrl);
        }

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'total' => $data['total'],
        ]);
    }

    //Cette fonction permette d'afficher les commandes a l'éditor et administrateur
    #[Route('/editor/order', name: 'app_orders_show')]
    public function getAllOrder(OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = $orderRepository->findBy([], ['id' => 'DESC']);
        //dd($order);
        $order = $paginator->paginate(
            $data,
            $request->query->getInt('page', default: 1),
            limit: 10
        );

        return $this->render('order/order.html.twig', [
            'orders' => $order
        ]);
    }


    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is_completed_update')]
    public function isCompletedUpdate($id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash(type: 'success', message: 'votre modiffication effectuée');

        return $this->redirectToRoute('app_orders_show');
    }


    #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash(type: 'danger', message: 'votre commande a été supprimée');

        return $this->redirectToRoute('app_orders_show');
    }



    #[Route('/order-ok-message', name: 'order-ok-message')]
    public function orderMessage(): Response
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
