<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePayment
{
    private $redirectUrl;

    public function __construct()
    {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET']);
        Stripe::setApiVersion('2024-06-20');
    }

    public function startPayment($cart, $shippingCost, $orderId)
    {
        $cartProducts = $cart['cart'];
        $lineItems = [];

        foreach ($cartProducts as $value) {
            $lineItems[] = [
                'quantity' => $value['quantity'],
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $value['product']->getName(),
                    ],
                    'unit_amount' => $value['product']->getPrice() * 100,
                ],
            ];
        }

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'cancel_url' => 'http://127.0.0.1:8000/pay/cancel',
            'success_url' => 'http://127.0.0.1:8000/pay/success',
            'billing_address_collection' => 'required',
            'shipping_options' => [
                [
                    'shipping_rate_data' => [
                        'display_name' => 'Frais de livraison',
                        'type' => 'fixed_amount',
                        'fixed_amount' => [
                            'amount' => $shippingCost * 100,
                            'currency' => 'eur',
                        ],
                    ],
                ],
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'orderId' => $orderId,
                ],
            ],
        ]);

        $this->redirectUrl = $session->url;
    }

    public function getStripeRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
