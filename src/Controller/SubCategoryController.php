<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Form\SubCategoryType;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sub/category')] // Route de base pour toutes les actions de gestion des sous-catégories
final class SubCategoryController extends AbstractController
{
    // Route pour afficher la liste des sous-catégories
    #[Route(name: 'app_sub_category_index', methods: ['GET'])]
    public function index(SubCategoryRepository $subCategoryRepository): Response
    {
        // Récupère toutes les sous-catégories et les passe à la vue
        return $this->render('sub_category/index.html.twig', [
            'sub_categories' => $subCategoryRepository->findAll(),
        ]);
    }

    // Route pour créer une nouvelle sous-catégorie
    #[Route('/new', name: 'app_sub_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crée une nouvelle instance de SubCategory et le formulaire associé
        $subCategory = new SubCategory();
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on sauvegarde la nouvelle sous-catégorie
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subCategory);
            $entityManager->flush();

            // Redirige vers la page d'index après la sauvegarde
            return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de création d'une sous-catégorie
        return $this->render('sub_category/new.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Route pour afficher les détails d'une sous-catégorie
    #[Route('/{id}', name: 'app_sub_category_show', methods: ['GET'])]
    public function show(SubCategory $subCategory): Response
    {
        // Passe la sous-catégorie à la vue pour l'affichage des détails
        return $this->render('sub_category/show.html.twig', [
            'sub_category' => $subCategory,
        ]);
    }

    // Route pour éditer une sous-catégorie existante
    #[Route('/{id}/edit', name: 'app_sub_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SubCategory $subCategory, EntityManagerInterface $entityManager): Response
    {
        // Crée et gère le formulaire d'édition de la sous-catégorie
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on met à jour la sous-catégorie
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Redirige vers la page d'index après la mise à jour
            return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire d'édition de la sous-catégorie
        return $this->render('sub_category/edit.html.twig', [
            'sub_category' => $subCategory,
            'form' => $form,
        ]);
    }

    // Route pour supprimer une sous-catégorie
    #[Route('/{id}', name: 'app_sub_category_delete', methods: ['POST'])]
    public function delete(Request $request, SubCategory $subCategory, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si le token CSRF est valide pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete' . $subCategory->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($subCategory); // Supprime la sous-catégorie
            $entityManager->flush(); // Applique les changements dans la base de données
        }

        // Redirige vers la page d'index après la suppression
        return $this->redirectToRoute('app_sub_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
