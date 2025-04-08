<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Cart;


final class StripeController extends AbstractController
{
    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(Cart $cart, SessionInterface $session): Response
    {
        //Recuperer le panier des produits
        $session->set('cart', []);

        return $this->render('stripe/index.html.twig', [
            'status' => 'success',
        ]);
    }

    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'status' => 'cancel',
        ]);
    }

    #[Route('/stripe/notify', name: 'app_stripe_notify')]
    public function stripeNotify(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): Response {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET']);

        $endpoint_secret = 'whsec_6667dd03f9fddd449304b19ab2c487504a82518a96506ae05c7b52e12ebf1a15';

        $playload = $request->getContent();
        $sig_header = $request->headers->get('stripe-signature');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $playload,
                $sig_header,
                $endpoint_secret

            );
        } catch (\UnexpectedValueException $e) {
            return new Response('payload invalide', 400);
        } catch (SignatureVerificationException) {
            return new Response('signature invalide');
        }
        switch ($event->type) {
            case 'payment_intent_succeeded': //Contient l'objet payement intent
                $paymentIntent = $event->data->object;
                $fileName = 'stripe-detail' . uniqid() . 'txt';

                $orderId = $paymentIntent->metadata->orderId;
                $order = $orderRepository->find($orderId);

                $cartPrice = $order->getTotalPrice();
                $stripTotalAmount = $paymentIntent->amount / 100;

                if ($cartPrice == $stripTotalAmount) {
                    $order->setIsPaymentCompleted(1);
                    $entityManager->flush();
                }


                //file_put_contents($fileName, $orderId);
                break;
            case 'payment_method_attached': //Contient objet payement method
                $paymentIntent = $event->data->object;
            default:
                # code...
                break;
        }

        return new Response('evenement reçu', 200);
    }
}
