<?php

namespace App\Service;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePayment
{
    private $redirectUrl;

    public function __construct()
    {
        //dd($_SERVER['STRIPE_SECRET']);
        Stripe::setApiKey($_SERVER['STRIPE_SECRET']);
        Stripe::setApiVersion('2024-06-20');
    }

    public function startPayment($cart, $shippingCost)
    {
        //dd($cart);
        $cartProducts = $cart['cart'];
        $products = [
            [
                'qte' => 1,
                'price' => $shippingCost,
                'name' => 'frais de livraison'
            ]
        ];

        foreach ($cartProducts as $value) {
            $productItem = [
                'name' => $value['product']->getName(),
                'price' => $value['product']->getPrice() * 100,
                'qte' => $value['quantity']
            ];
            $products[] = $productItem;
        }
        $session = Session::create([
            'line_items' => array_map(fn($product) => [
                'quantity' => $product['qte'],
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product['name']
                    ],
                    'unit_amount' => $product['price']
                ],
            ], $products),
            'mode' => 'payment',
            'cancel_url' => 'http://127.0.0.1:8000/pay/cancel',
            'success_url' => 'http://127.0.0.1:8000/pay/success',
            'billing_address_collection' => 'required',
        ]);
        $this->redirectUrl = $session->url;

        // $session = Session::create([
        //     'line_items' => [
        //         array_map(fn(array $product) => [
        //             'quantity' => $product['qte'],
        //             'price_data' => [
        //                 'currency' => 'euro',
        //                 'product.data' => [
        //                     'name' => $product['name']
        //                 ],
        //                 'unit_amount' => $product['price']
        //             ],
        //         ], $products)

        //     ],
        //     'mode' => 'payment',
        //     'cancel_url' => 'http://127.0.0.1:8000/pay/cancel',
        //     'success_url' => 'http://127.0.0.1:8000/pay/success',
        //     'billing_address_collection' => 'required',
        //     // 'shipping_address_collection'=>[
        //     //     'allowed_countries'=>["FR","CM"]
        //     // ],
        //     'metadata' => []

        // ]);

    }

    public function getStripeRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
