<?php
namespace App\Service;
use App\Repository\ProductRepository;

class Cart
{
    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getCart($session): array
    {
        //Remplire le panier d'achat du produit
        $cart = $session->get('cart', []);
        $cartWhitData = [];
        foreach ($cart as $id => $quantity) {
            $cartWhitData[] = [
                'product' => $this->productRepository->find($id),
                'quantity' => $quantity
            ];
        }
        //Renvoyer a l'utilsateur le pri total
        $total = array_sum(array_map(function ($item) {
            return $item['product']->getPrice() * $item['quantity'];
        }, $cartWhitData));
        return [
            'cart'=>$cartWhitData,
            'total'=>$total
        ];
    }
}