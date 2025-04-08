<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/editor/city')]
final class CityController extends AbstractController
{
    /// 1- Cette fonction permet d'afficher la liste des villes disponibles
    #[Route(name: 'app_city_index', methods: ['GET'])]
    public function index(CityRepository $cityRepository): Response
    {
        return $this->render('city/index.html.twig', [
            'cities' => $cityRepository->findAll(), // Récupère toutes les villes depuis la base de données
        ]);
    }


    /// 2- Cette fonction permet d'ajouter une nouvelle ville
    #[Route('/new', name: 'app_city_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = new City(); // Création d'une nouvelle instance de City
        $form = $this->createForm(CityType::class, $city); // Création du formulaire associé à l'entité City
        $form->handleRequest($request); // Gestion de la requête et soumission du formulaire

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide
            $entityManager->persist($city); // Persiste la nouvelle ville dans l'entité
            $entityManager->flush(); // Sauvegarde les modifications dans la base de données

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('city/new.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }


    /// 3- Cette fonction permet d'afficher les détails d'une ville spécifique
    #[Route('/{id}', name: 'app_city_show', methods: ['GET'])]
    public function show(City $city): Response
    {
        return $this->render('city/show.html.twig', [
            'city' => $city, // Envoie l'objet City à la vue
        ]);
    }


    /// 4- Cette fonction permet de modifier une ville existante
    #[Route('/{id}/edit', name: 'app_city_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CityType::class, $city); // Création du formulaire de modification
        $form->handleRequest($request); // Gestion de la requête et soumission du formulaire

        if ($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire est soumis et valide
            $entityManager->flush(); // Sauvegarde les modifications dans la base de données

            return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('city/edit.html.twig', [
            'city' => $city,
            'form' => $form,
        ]);
    }

    /// 5- Cette fonction permet de supprimer une ville
    #[Route('/{id}', name: 'app_city_delete', methods: ['POST'])]
    public function delete(Request $request, City $city, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si le token CSRF est valide pour prévenir les attaques CSRF
        if ($this->isCsrfTokenValid('delete' . $city->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($city); // Supprime la ville de la base de données
            $entityManager->flush(); // Sauvegarde les modifications
        }

        return $this->redirectToRoute('app_city_index', [], Response::HTTP_SEE_OTHER);
    }
}
