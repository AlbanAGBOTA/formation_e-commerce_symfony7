<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchEngineController extends AbstractController
{
    // Route pour gérer la recherche des produits
    #[Route('/search/engine', name: 'app_search_engine', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        // Vérifie si la requête est bien de type GET
        if ($request->isMethod('GET')) {
            // Récupère tous les paramètres de la requête
            $data = $request->query->all();

            // Récupère le mot clé de recherche
            $word = $data['word'];

            // Recherche les produits correspondant au mot clé
            $result = $productRepository->searchEngine($word);
        }

        // Affiche la vue avec les résultats de la recherche
        return $this->render('search_engine/index.html.twig', [
            'products' => $result,
            'word' => $word
        ]);
    }
}
